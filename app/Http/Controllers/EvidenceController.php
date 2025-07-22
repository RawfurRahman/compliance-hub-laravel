<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\PciDssRequirement;
use App\Models\EvidenceFile; // Ensure this is imported
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class EvidenceController extends Controller
{
    /**
     * Display the evidence management page for a specific project.
     */
    public function show(Project $project)
    {
        // Eager load relationships for efficiency
        $requirements = PciDssRequirement::all()->sortBy('req_num', SORT_NATURAL);
        // Load evidence files with user and approvedBy relationships
        $project->load('evidenceFiles.user', 'evidenceFiles.approvedBy', 'chatMessages.user.roles');

        $evidenceByRequirement = $project->evidenceFiles->groupBy('pci_dss_requirement_id');

        return view('evidence.show', [
            'project' => $project,
            'requirements' => $requirements,
            'evidenceByRequirement' => $evidenceByRequirement,
            'chatMessages' => $project->chatMessages
        ]);
    }

    /**
     * Handle the file upload process and trigger n8n workflow.
     */
    public function upload(Request $request, Project $project)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // Max 20MB
            'requirement_id' => 'required|exists:pci_dss_requirements,id',
        ]);

        $file = $request->file('file');
        // Store the file in a project-specific folder within 'public/storage/evidence'
        $path = $file->store("evidence/{$project->id}", 'public');

        $evidence = $project->evidenceFiles()->create([
            'pci_dss_requirement_id' => $request->requirement_id,
            'user_id' => auth()->id(),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'scan_status' => 'pending', // Initial status
            'ai_analysis_status' => 'pending', // Initial status
        ]);

        // ** n8n INTEGRATION POINT: Trigger File Security Scan Workflow **
        $n8nScanWebhookUrl = env('N8N_FILE_SCAN_WEBHOOK_URL');
        if ($n8nScanWebhookUrl) {
            try {
                // We send the public URL of the file for n8n to download and scan
                Http::timeout(60)->post($n8nScanWebhookUrl, [ // Add timeout for webhook
                    'evidence_file_id' => $evidence->id,
                    'file_url' => asset('storage/' . $path), // Use asset() to get public URL
                    'original_filename' => $evidence->original_filename,
                    'mime_type' => $evidence->mime_type,
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'requirement_id' => $evidence->pci_dss_requirement_id,
                ]);
                Log::info("n8n file scan webhook triggered for evidence_file_id: {$evidence->id}");
            } catch (\Exception $e) {
                Log::error('Failed to trigger n8n file scan workflow: ' . $e->getMessage());
                // Update evidence status to reflect webhook failure
                $evidence->update(['scan_status' => 'webhook_failed']);
            }
        } else {
            Log::warning('N8N_FILE_SCAN_WEBHOOK_URL is not set in .env');
            $evidence->update(['scan_status' => 'n8n_not_configured']);
        }

        return back()->with('success', 'File uploaded successfully and is being processed by security and AI analysis workflows.');
    }

    /**
     * n8n Callback: Receive File Security Scan results.
     */
    public function n8nFileScanCallback(Request $request)
    {
        // Validate incoming data from n8n
        $request->validate([
            'evidence_file_id' => 'required|exists:evidence_files,id',
            'scan_status' => 'required|string|in:clean,infected,failed',
            'scan_details' => 'nullable|array',
        ]);

        $evidenceFile = EvidenceFile::find($request->evidence_file_id);

        if (!$evidenceFile) {
            Log::warning("n8nFileScanCallback: EvidenceFile ID {$request->evidence_file_id} not found.");
            return response()->json(['status' => 'error', 'message' => 'Evidence file not found'], 404);
        }

        $evidenceFile->scan_status = $request->scan_status;
        $evidenceFile->scan_details = $request->scan_details;
        $evidenceFile->save();

        Log::info("EvidenceFile ID {$evidenceFile->id} scan status updated to: {$request->scan_status}");

        // ** n8n INTEGRATION POINT: Trigger LLM Analysis Workflow if scan is clean **
        if ($evidenceFile->scan_status === 'clean') {
            $n8nAiAnalysisWebhookUrl = env('N8N_AI_ANALYSIS_WEBHOOK_URL');
            if ($n8nAiAnalysisWebhookUrl) {
                try {
                    Http::timeout(120)->post($n8nAiAnalysisWebhookUrl, [ // Longer timeout for AI analysis
                        'evidence_file_id' => $evidenceFile->id,
                        'file_url' => asset('storage/' . $evidenceFile->file_path),
                        'original_filename' => $evidenceFile->original_filename,
                        'mime_type' => $evidenceFile->mime_type,
                        'requirement_id' => $evidenceFile->pci_dss_requirement_id,
                        'project_id' => $evidenceFile->project_id,
                    ]);
                    $evidenceFile->update(['ai_analysis_status' => 'processing']);
                    Log::info("n8n AI analysis webhook triggered for evidence_file_id: {$evidenceFile->id}");
                } catch (\Exception $e) {
                    Log::error('Failed to trigger n8n AI analysis workflow: ' . $e->getMessage());
                    $evidenceFile->update(['ai_analysis_status' => 'webhook_failed']);
                }
            } else {
                Log::warning('N8N_AI_ANALYSIS_WEBHOOK_URL is not set in .env');
                $evidenceFile->update(['ai_analysis_status' => 'n8n_not_configured']);
            }
        } else {
            // If scan is not clean, mark AI analysis as not applicable or failed
            $evidenceFile->update(['ai_analysis_status' => 'skipped_due_to_scan']);
        }

        return response()->json(['status' => 'success', 'message' => 'Scan result received and processed']);
    }

    /**
     * n8n Callback: Receive AI Analysis results (observations and recommendations).
     */
    public function n8nAiAnalysisCallback(Request $request)
    {
        // Validate incoming data from n8n
        $request->validate([
            'evidence_file_id' => 'required|exists:evidence_files,id',
            'observations' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'status' => 'required|string|in:completed,failed', // Status from AI processing
        ]);

        $evidenceFile = EvidenceFile::find($request->evidence_file_id);

        if (!$evidenceFile) {
            Log::warning("n8nAiAnalysisCallback: EvidenceFile ID {$request->evidence_file_id} not found.");
            return response()->json(['status' => 'error', 'message' => 'Evidence file not found'], 404);
        }

        $evidenceFile->ai_observations = $request->observations;
        $evidenceFile->ai_recommendations = $request->recommendations;
        $evidenceFile->ai_analysis_status = ($request->status === 'completed') ? 'awaiting_review' : 'failed'; // Mark as awaiting review
        $evidenceFile->save();

        Log::info("EvidenceFile ID {$evidenceFile->id} AI analysis status updated to: {$evidenceFile->ai_analysis_status}");

        // ** n8n INTEGRATION POINT: Trigger HITL Workflow (Email Auditor) **
        $n8nHitlWebhookUrl = env('N8N_HITL_WEBHOOK_URL');
        if ($n8nHitlWebhookUrl && $evidenceFile->ai_analysis_status === 'awaiting_review') {
            try {
                // Pass relevant data for the auditor notification
                Http::post($n8nHitlWebhookUrl, [
                    'evidence_file_id' => $evidenceFile->id,
                    'file_name' => $evidenceFile->original_filename,
                    'project_id' => $evidenceFile->project_id,
                    'project_name' => optional($evidenceFile->project)->name,
                    'requirement_id' => $evidenceFile->pci_dss_requirement_id,
                    'requirement_num' => optional($evidenceFile->requirement)->req_num,
                    'auditor_email' => 'auditor@example.com', // Replace with actual assigned auditor's email
                    'review_link' => route('evidence.show', ['project' => $evidenceFile->project_id]) . '#evidence-file-' . $evidenceFile->id, // Link to the specific file on the frontend
                ]);
                Log::info("n8n HITL webhook triggered for evidence_file_id: {$evidenceFile->id}");
            } catch (\Exception $e) {
                Log::error('Failed to trigger n8n HITL workflow: ' . $e->getMessage());
            }
        } else {
            Log::warning('N8N_HITL_WEBHOOK_URL is not set or AI analysis not awaiting review.');
        }

        return response()->json(['status' => 'success', 'message' => 'AI analysis result received and processed']);
    }

    /**
     * API endpoint for auditor to approve AI analysis.
     */
    public function approveAiAnalysis(Request $request, EvidenceFile $evidenceFile)
    {
        // Ensure only authenticated auditors can approve
        if (!Auth::check() || !Auth::user()->hasRole('Auditor')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        // Validate that the AI analysis is actually awaiting review
        if ($evidenceFile->ai_analysis_status !== 'awaiting_review') {
            return response()->json(['status' => 'error', 'message' => 'AI analysis is not awaiting review.'], 400);
        }

        $evidenceFile->ai_analysis_status = 'approved';
        $evidenceFile->ai_analysis_approved_by = Auth::id();
        $evidenceFile->ai_analysis_approved_at = now();
        $evidenceFile->save();

        Log::info("EvidenceFile ID {$evidenceFile->id} AI analysis approved by user ID: " . Auth::id());

        return response()->json(['status' => 'success', 'message' => 'AI analysis approved successfully!']);
    }

    /**
     * API endpoint for auditor to reject/edit AI analysis.
     */
    public function rejectAiAnalysis(Request $request, EvidenceFile $evidenceFile)
    {
        // Ensure only authenticated auditors can reject/edit
        if (!Auth::check() || !Auth::user()->hasRole('Auditor')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        // Validate that the AI analysis is actually awaiting review
        if ($evidenceFile->ai_analysis_status !== 'awaiting_review') {
            return response()->json(['status' => 'error', 'message' => 'AI analysis is not awaiting review.'], 400);
        }

        $request->validate([
            'auditor_notes' => 'nullable|string', // Optional notes from auditor
            'new_observations' => 'nullable|string', // Auditor can edit observations
            'new_recommendations' => 'nullable|string', // Auditor can edit recommendations
        ]);

        $evidenceFile->ai_analysis_status = 'rejected'; // Or 'edited' if you want a separate status
        $evidenceFile->ai_observations = $request->new_observations ?? $evidenceFile->ai_observations;
        $evidenceFile->ai_recommendations = $request->new_recommendations ?? $evidenceFile->ai_recommendations;
        // You might want to store auditor_notes in a separate field or log it
        $evidenceFile->save();

        Log::info("EvidenceFile ID {$evidenceFile->id} AI analysis rejected/edited by user ID: " . Auth::id());

        return response()->json(['status' => 'success', 'message' => 'AI analysis rejected/edited.']);
    }


    /**
     * Fetch the latest chat messages for a project (for real-time polling).
     */
    public function getMessages(Project $project)
    {
        // Ensure only authorized users can view chat messages for a project
        // For example, if (Auth::user()->cannot('view-project-chat', $project)) { abort(403); }
        // For now, assuming anyone authenticated can view.
        $messages = $project->chatMessages()->with('user.roles')->latest()->take(50)->get()->reverse();
        return response()->json($messages);
    }

    /**
     * Store a new chat message.
     */
    public function postMessage(Request $request, Project $project)
    {
        $request->validate(['message' => 'required|string']);

        $message = $project->chatMessages()->create([
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);
        
        $message->load('user.roles'); // Eager load user and roles for the response

        // ** n8n INTEGRATION POINT: Trigger Chat Notification Workflow (if needed, or poll from n8n) **
        // This could be a direct webhook or n8n can poll getUnreadMessages.
        // For real-time, a websocket solution is better, but for email notifications, polling is fine.
        // If you want to trigger n8n immediately on *any* message for notification, uncomment below.
        /*
        $n8nChatWebhookUrl = env('N8N_CHAT_MESSAGE_WEBHOOK_URL');
        if ($n8nChatWebhookUrl) {
            try {
                Http::post($n8nChatWebhookUrl, [
                    'message_id' => $message->id,
                    'project_id' => $project->id,
                    'user_id' => $message->user_id,
                    'message_text' => $message->message,
                    'timestamp' => $message->created_at->toIso8601String(),
                ]);
                Log::info("n8n chat message webhook triggered for message_id: {$message->id}");
            } catch (\Exception $e) {
                Log::error('Failed to trigger n8n chat message workflow: ' . $e->getMessage());
            }
        }
        */

        return response()->json($message);
    }
    
    /**
     * API endpoint for n8n to fetch unread messages older than 5 minutes.
     * This is polled by n8n to send notifications.
     */
    public function getUnreadMessages()
    {
        // Fetch messages that are not read and are older than 5 minutes
        $messages = \App\Models\ChatMessage::whereNull('read_at')
            ->where('created_at', '<=', Carbon::now()->subMinutes(5))
            ->with('user', 'project.user') // Eager load relationships needed for the notification
            ->get();
            
        // Mark messages as read to prevent re-sending notifications by n8n
        // This should ideally be done AFTER n8n successfully processes the notification.
        // For simplicity here, we mark them as read immediately.
        foreach($messages as $message) {
            $message->update(['read_at' => now()]);
        }

        return response()->json($messages);
    }
}

