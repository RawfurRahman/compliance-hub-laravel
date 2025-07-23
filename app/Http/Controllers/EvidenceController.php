<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\PciDssRequirement;
use App\Models\EvidenceFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    /**
     * Display the evidence management page for a specific project.
     */
    public function show(Project $project)
    {
        $requirements = PciDssRequirement::all()->sortBy('req_num', SORT_NATURAL);
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
     * Handle the file upload process and trigger the first n8n workflow.
     * **MODIFIED FOR MULTIPART FILE UPLOAD**
     */
    public function upload(Request $request, Project $project)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // Max 20MB
            'requirement_id' => 'required|exists:pci_dss_requirements,id',
        ]);

        $file = $request->file('file');
        $path = $file->store("evidence/{$project->id}", 'public');

        $evidence = $project->evidenceFiles()->create([
            'pci_dss_requirement_id' => $request->requirement_id,
            'user_id' => auth()->id(),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'scan_status' => 'pending',
            'ai_analysis_status' => 'pending',
        ]);

        // ** THE FIX IS HERE **
        // We now send the file directly as a multipart/form-data attachment.
        // This is a more standard and efficient way to handle file uploads.
        $n8nScanWebhookUrl = env('N8N_FILE_SCAN_WEBHOOK_URL');
        if ($n8nScanWebhookUrl) {
            try {
                Http::timeout(60)
                    ->attach(
                        'file', // This is the name n8n will use for the binary data
                        Storage::disk('public')->get($path),
                        $evidence->original_filename
                    )
                    ->post($n8nScanWebhookUrl, [
                        'evidence_file_id' => $evidence->id,
                        'project_id' => $project->id,
                    ]);

                Log::info("n8n file scan webhook triggered for evidence_file_id: {$evidence->id}");
            } catch (\Exception $e) {
                Log::error('Failed to trigger n8n file scan workflow: ' . $e->getMessage());
                $evidence->update(['scan_status' => 'webhook_failed']);
            }
        } else {
            Log::warning('N8N_FILE_SCAN_WEBHOOK_URL is not set in .env');
            $evidence->update(['scan_status' => 'n8n_not_configured']);
        }

        return back()->with('success', 'File uploaded and sent for security scanning.');
    }

    /**
     * n8n Callback 1: Receive File Security Scan results.
     * **MODIFIED FOR MULTIPART FILE UPLOAD**
     */
    public function n8nFileScanCallback(Request $request)
    {
        $request->validate([
            'evidence_file_id' => 'required|exists:evidence_files,id',
            'scan_status' => 'required|string|in:clean,infected,failed',
            'scan_details' => 'nullable|array',
        ]);

        $evidenceFile = EvidenceFile::find($request->evidence_file_id);
        if (!$evidenceFile) {
            return response()->json(['status' => 'error', 'message' => 'Evidence file not found'], 404);
        }

        $evidenceFile->update([
            'scan_status' => $request->scan_status,
            'scan_details' => $request->scan_details,
        ]);

        Log::info("EvidenceFile ID {$evidenceFile->id} scan status updated to: {$request->scan_status}");

        if ($evidenceFile->scan_status === 'clean') {
            $n8nAiAnalysisWebhookUrl = env('N8N_AI_ANALYSIS_WEBHOOK_URL');
            if ($n8nAiAnalysisWebhookUrl) {
                try {
                    // Send the file again as a multipart attachment for the AI analysis workflow.
                    Http::timeout(120)
                        ->attach(
                            'file',
                            Storage::disk('public')->get($evidenceFile->file_path),
                            $evidenceFile->original_filename
                        )
                        ->post($n8nAiAnalysisWebhookUrl, [
                            'evidence_file_id' => $evidenceFile->id,
                            'requirement_text' => optional($evidenceFile->requirement)->req_description,
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
            $evidenceFile->update(['ai_analysis_status' => 'skipped_due_to_scan']);
        }

        return response()->json(['status' => 'success', 'message' => 'Scan result received.']);
    }

    // ... The rest of the controller methods (AI callback, approval, chat, etc.) remain the same ...
    public function n8nAiAnalysisCallback(Request $request)
    {
        $request->validate([
            'evidence_file_id' => 'required|exists:evidence_files,id',
            'observations' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'status' => 'required|string|in:completed,failed',
        ]);

        $evidenceFile = EvidenceFile::find($request->evidence_file_id);
        if (!$evidenceFile) {
            return response()->json(['status' => 'error', 'message' => 'Evidence file not found'], 404);
        }

        $evidenceFile->update([
            'ai_observations' => $request->observations,
            'ai_recommendations' => $request->recommendations,
            'ai_analysis_status' => ($request->status === 'completed') ? 'awaiting_review' : 'failed',
        ]);

        Log::info("EvidenceFile ID {$evidenceFile->id} AI analysis status updated to: {$evidenceFile->ai_analysis_status}");

        $n8nHitlWebhookUrl = env('N8N_HITL_WEBHOOK_URL');
        if ($n8nHitlWebhookUrl && $evidenceFile->ai_analysis_status === 'awaiting_review') {
            try {
                $auditor = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Auditor'))->first();

                Http::post($n8nHitlWebhookUrl, [
                    'evidence_file_id' => $evidenceFile->id,
                    'file_name' => $evidenceFile->original_filename,
                    'project_name' => optional($evidenceFile->project)->name,
                    'requirement_num' => optional($evidenceFile->requirement)->req_num,
                    'auditor_email' => $auditor ? $auditor->email : 'default-auditor@example.com',
                    'review_link' => route('evidence.show', ['project' => $evidenceFile->project_id]) . '#evidence-file-' . $evidenceFile->id,
                ]);
                Log::info("n8n HITL webhook triggered for evidence_file_id: {$evidenceFile->id}");
            } catch (\Exception $e) {
                Log::error('Failed to trigger n8n HITL workflow: ' . $e->getMessage());
            }
        }

        return response()->json(['status' => 'success', 'message' => 'AI analysis result received.']);
    }

    public function approveAiAnalysis(EvidenceFile $evidenceFile)
    {
        $this->authorize('is-auditor');
        if ($evidenceFile->ai_analysis_status !== 'awaiting_review') {
            return response()->json(['status' => 'error', 'message' => 'This analysis is not awaiting review.'], 400);
        }
        $evidenceFile->update([
            'ai_analysis_status' => 'approved',
            'ai_analysis_approved_by' => Auth::id(),
            'ai_analysis_approved_at' => now(),
        ]);
        return response()->json(['status' => 'success', 'message' => 'AI analysis approved!']);
    }

    public function rejectAiAnalysis(Request $request, EvidenceFile $evidenceFile)
    {
        $this->authorize('is-auditor');
        if ($evidenceFile->ai_analysis_status !== 'awaiting_review') {
            return response()->json(['status' => 'error', 'message' => 'This analysis is not awaiting review.'], 400);
        }
        $evidenceFile->update([
            'ai_analysis_status' => 'rejected',
            'ai_analysis_approved_by' => Auth::id(),
            'ai_analysis_approved_at' => now(),
        ]);
        return response()->json(['status' => 'success', 'message' => 'AI analysis rejected.']);
    }

    public function getMessages(Project $project)
    {
        $messages = $project->chatMessages()->with('user.roles')->latest()->take(50)->get()->reverse();
        return response()->json($messages);
    }

    public function postMessage(Request $request, Project $project)
    {
        $request->validate(['message' => 'required|string']);
        $message = $project->chatMessages()->create([
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);
        $message->load('user.roles');
        return response()->json($message);
    }
    
    public function getUnreadMessages()
    {
        $messages = \App\Models\ChatMessage::whereNull('read_at')
            ->where('created_at', '<=', Carbon::now()->subMinutes(5))
            ->with('user', 'project.user')
            ->get();
            
        foreach($messages as $message) {
            $message->update(['read_at' => now()]);
        }
        return response()->json($messages);
    }
}
