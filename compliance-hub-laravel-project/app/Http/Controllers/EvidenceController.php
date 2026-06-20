<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\PciDssRequirement;
use App\Models\EvidenceFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\ZipExportService;
use Illuminate\Support\Facades\DB;
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
        $project->load('evidenceFiles.user', 'evidenceFiles.approvedBy', 'chatMessages.user.roles', 'pciDssDetails.findings');
        $evidenceByRequirement = $project->evidenceFiles->groupBy('pci_dss_requirement_id');

        $findings = $project->pciDssDetails ? $project->pciDssDetails->findings->keyBy('pci_dss_requirement_id') : collect();

        // Filter out non-applicable requirements for everyone in the Evidence Hub.
        // To toggle scope, the Auditor will use the PCI Assessment form.
        $requirements = $requirements->filter(function ($req) use ($findings) {
            $finding = $findings->get($req->id);
            return !($finding && $finding->is_applicable === false);
        });

        return view('evidence.show', [
            'project' => $project,
            'requirements' => $requirements,
            'evidenceByRequirement' => $evidenceByRequirement,
            'chatMessages' => $project->chatMessages,
            'findings' => $findings
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
        // ** FIXED: NON-BLOCKING TRIGGER **
        // We use a 1-second timeout to "fire and forget" this request. 
        // This prevents a deadlock in single-threaded php artisan serve.
        $n8nScanWebhookUrl = env('N8N_FILE_SCAN_WEBHOOK_URL', 'http://localhost:5678/webhook/file-scan');
        if ($n8nScanWebhookUrl) {
            try {
                Http::timeout(1)->retry(0)
                    ->attach(
                        'file',
                        Storage::disk('public')->get($path),
                        $evidence->original_filename
                    )
                    ->attach('evidence_file_id', $evidence->id)
                    ->attach('project_id', $project->id)
                    ->post($n8nScanWebhookUrl);

                Log::info("n8n file scan webhook triggered for evidence_file_id: {$evidence->id}");
            } catch (\Exception $e) {
                // If it's just a timeout, n8n likely received it and is processing.
                Log::info('n8n scan trigger hit 1s timeout (expected): ' . $e->getMessage());
            }
        } else {
            Log::warning('N8N_FILE_SCAN_WEBHOOK_URL is not set in .env');
            $evidence->update(['scan_status' => 'n8n_not_configured']);
        }

        return back()->with('success', 'File uploaded and sent for security scanning.');
    }

    /**
     * n8n Callback 1: Receive File Security Scan results.
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

        // Handle Infected Files: Immediate Deletion for Security
        if ($evidenceFile->scan_status === 'infected') {
            Log::warning("SECURITY ALERT: Infected file detected. Deleting EvidenceFile ID {$evidenceFile->id} and physical file: {$evidenceFile->file_path}");
            
            // 1. Delete physical file from storage
            if (Storage::disk('public')->exists($evidenceFile->file_path)) {
                Storage::disk('public')->delete($evidenceFile->file_path);
            }

            // 2. Delete database record
            $evidenceFile->delete();

            return response()->json([
                'status' => 'security_action_taken', 
                'message' => 'Infected file detected and permanently deleted for security.'
            ]);
        }

        if ($evidenceFile->scan_status === 'clean') {
            $n8nAiAnalysisWebhookUrl = env('N8N_AI_ANALYSIS_WEBHOOK_URL');
            if ($n8nAiAnalysisWebhookUrl) {
                try {
                    // ** FIXED: NON-BLOCKING TRIGGER **
                    Http::timeout(1)->retry(0)
                        ->attach(
                            'file',
                            Storage::disk('public')->get($evidenceFile->file_path),
                            $evidenceFile->original_filename
                        )
                        ->attach('evidence_file_id', $evidenceFile->id)
                        ->attach('requirement_text', optional($evidenceFile->requirement)->req_description ?? '')
                        ->attach('original_filename', $evidenceFile->original_filename)
                        ->post($n8nAiAnalysisWebhookUrl);

                    $evidenceFile->update(['ai_analysis_status' => 'processing']);
                    Log::info("n8n AI analysis webhook triggered for evidence_file_id: {$evidenceFile->id}");
                } catch (\Exception $e) {
                    Log::info('n8n AI trigger hit 1s timeout (expected): ' . $e->getMessage());
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
        if (!auth()->user()->hasRole('Auditor') && !auth()->user()->hasRole('Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
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
        if (!auth()->user()->hasRole('Auditor') && !auth()->user()->hasRole('Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $rejectionNote = $request->input('note', '');

        // Save the rejection note as feedback if provided
        if (!empty($rejectionNote)) {
            $evidenceFile->feedbacks()->create([
                'user_id' => auth()->id(),
                'message' => '[AI Rejection Note] ' . $rejectionNote,
            ]);
        }

        $n8nAiAnalysisWebhookUrl = env('N8N_AI_ANALYSIS_WEBHOOK_URL');
        if ($n8nAiAnalysisWebhookUrl) {
            try {
                $fileContents = '';
                if (Storage::disk('public')->exists($evidenceFile->file_path)) {
                    $fileContents = Storage::disk('public')->get($evidenceFile->file_path);
                } else {
                    $fileContents = 'Re-analysis requested for ' . $evidenceFile->original_filename;
                }

                Http::timeout(2)->retry(0)
                    ->attach(
                        'file',
                        $fileContents,
                        $evidenceFile->original_filename
                    )
                    ->attach('evidence_file_id', (string) $evidenceFile->id)
                    ->attach('requirement_text', optional($evidenceFile->requirement)->req_description ?? '')
                    ->attach('original_filename', $evidenceFile->original_filename)
                    ->post($n8nAiAnalysisWebhookUrl);

                $evidenceFile->update([
                    'ai_analysis_status' => 'processing',
                    'ai_observations' => 'Re-analysis in progress...',
                    'ai_recommendations' => '',
                    'hitl_status' => 'pending_review',
                ]);
                Log::info("AI re-analysis triggered for evidence_file_id: {$evidenceFile->id}");
            } catch (\Exception $e) {
                // Timeout is expected with fire-and-forget pattern
                Log::info('n8n AI re-trigger timeout (expected): ' . $e->getMessage());
                $evidenceFile->update([
                    'ai_analysis_status' => 'processing',
                    'ai_observations' => 'Re-analysis in progress...',
                    'ai_recommendations' => '',
                    'hitl_status' => 'pending_review',
                ]);
            }
        } else {
            Log::warning('N8N_AI_ANALYSIS_WEBHOOK_URL is not set in .env');
            $evidenceFile->update([
                'ai_analysis_status' => 'n8n_not_configured',
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'AI analysis rejected and re-triggered.']);
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

    public function submitFeedback(Request $request, EvidenceFile $evidenceFile)
    {
        $request->validate([
            'message' => 'required|string',
            'action' => 'required|in:accept,return,reply',
            'file' => 'nullable|file|max:20480',
        ]);

        $evidenceFile->feedbacks()->create([
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        if ($request->action === 'accept') {
            $evidenceFile->update(['hitl_status' => 'accepted']);
        } elseif ($request->action === 'return') {
            $evidenceFile->update(['hitl_status' => 'action_required']);
        } elseif ($request->action === 'reply') {
            $evidenceFile->update([
                'hitl_status' => 'pending_review',
                'customer_response' => $request->message,
            ]);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('evidence_files', $filename, 'public');

                $evidenceFile->update([
                    'original_filename' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'scan_status' => 'pending',
                    'ai_analysis_status' => 'pending',
                ]);
            }
        }

        return response()->json(['message' => 'Feedback submitted successfully', 'status' => 'success']);
    }

    /**
     * Export all 'accepted' evidence for this project into a structured ZIP package.
     */
    public function exportZip(Project $project, ZipExportService $zipService)
    {
        if (!auth()->user()->hasRole('Auditor') && !auth()->user()->hasRole('Admin')) {
            abort(403, 'Unauthorized');
        }
        try {
            $export = $zipService->createEvidencePackage($project);
            return response()->download($export['path']);
        } catch (\Exception $e) {
            Log::error('Evidence ZIP export failed: ' . $e->getMessage());
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the 'is_applicable' (In-Scope vs N/A) status for a requirement.
     */
    public function toggleScope(Request $request, Project $project, PciDssRequirement $requirement)
    {
        if (!auth()->user()->hasRole('Auditor') && !auth()->user()->hasRole('Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $request->validate(['is_applicable' => 'required|boolean']);

        if ($project->pciDssDetails) {
            $project->pciDssDetails->findings()->updateOrCreate(
                ['pci_dss_requirement_id' => $requirement->id],
                ['is_applicable' => $request->is_applicable]
            );
        }

        return response()->json(['status' => 'success', 'message' => 'Requirement scope updated.']);
    }

    /**
     * Get the latest project activities (uploads, reviews, comments) for the 'Pulse' sidebar.
     */
    public function getLatestActivities(Project $project)
    {
        // 1. Latest Uploads
        $uploads = $project->evidenceFiles()->with('user', 'requirement')->latest()->take(3)->get()->map(fn($f) => [
            'type' => 'upload',
            'user' => $f->user->username,
            'req'  => $f->requirement->req_num,
            'time' => $f->created_at->diffForHumans(),
            'icon' => 'fa-cloud-upload-alt text-sky-500'
        ]);

        // 2. Latest Feedbacks
        $feedbacks = DB::table('evidence_feedbacks')
            ->join('evidence_files', 'evidence_feedbacks.evidence_file_id', '=', 'evidence_files.id')
            ->join('users', 'evidence_feedbacks.user_id', '=', 'users.id')
            ->where('evidence_files.project_id', $project->id)
            ->select('users.username', 'evidence_feedbacks.message', 'evidence_feedbacks.created_at')
            ->latest('evidence_feedbacks.created_at')
            ->take(3)
            ->get()
            ->map(fn($f) => [
                'type' => 'comment',
                'user' => $f->username,
                'time' => Carbon::parse($f->created_at)->diffForHumans(),
                'icon' => 'fa-comments text-indigo-500'
            ]);

        $activities = $uploads->concat($feedbacks)->sortByDesc('time')->take(5)->values();

        return response()->json($activities);
    }

    /**
     * Real-time status polling endpoint — returns current processing state.
     * UPDATED for Granular feedback.
     */
    public function getStatus(EvidenceFile $evidenceFile)
    {
        $statusLabel = "Initializing Process...";
        
        if ($evidenceFile->scan_status === 'pending') {
            $statusLabel = "Scanning for vulnerabilities (ClamAV)...";
        } elseif ($evidenceFile->scan_status === 'clean' && $evidenceFile->ai_analysis_status === 'pending') {
            $statusLabel = "Transmitting to Gemini AI Core...";
        } elseif ($evidenceFile->ai_analysis_status === 'processing') {
            $statusLabel = "AI is analyzing document context...";
        } elseif ($evidenceFile->ai_analysis_status === 'awaiting_review') {
            $statusLabel = "Awaiting Auditor HITL Validation";
        } elseif ($evidenceFile->hitl_status === 'accepted') {
            $statusLabel = "Evidence Approved & Locked";
        }

        return response()->json([
            'id'                  => $evidenceFile->id,
            'scan_status'         => $evidenceFile->scan_status,
            'ai_analysis_status'  => $evidenceFile->ai_analysis_status,
            'hitl_status'         => $evidenceFile->hitl_status,
            'status_label'        => $statusLabel,
            'ai_observations'     => $evidenceFile->ai_observations,
            'ai_recommendations'  => $evidenceFile->ai_recommendations,
            'ai_approved_by'      => optional($evidenceFile->approvedBy)->username,
            'ai_approved_at'      => $evidenceFile->ai_analysis_approved_at?->toDateTimeString(),
        ]);
    }

    /**
     * Send AI analysis report via email (triggered by n8n)
     */
    public function sendAiAnalysisMail(Request $request)
    {
        $validated = $request->validate([
            'observations' => 'required|string',
            'recommendations' => 'required|string',
            'file_name' => 'required|string',
            'to_email' => 'required|email',
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to($validated['to_email'])->send(new \App\Mail\AiAnalysisReport(
                $validated['observations'],
                $validated['recommendations'],
                $validated['file_name']
            ));

            return response()->json(['message' => 'AI analysis email sent successfully']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Analysis Email Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download or retrieve the physical evidence file.
     */
    public function getFile($id)
    {
        $evidenceFile = \App\Models\EvidenceFile::findOrFail($id);
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($evidenceFile->file_path)) {
            abort(404, 'File not found');
        }
        return response()->file(\Illuminate\Support\Facades\Storage::disk('public')->path($evidenceFile->file_path));
    }

    /**
     * Show the flat Evidence Hub page matching the user's dashboard image mockup.
     */
    public function hub(\App\Models\Project $project = null)
    {
        if (!$project) {
            $project = \App\Models\Project::first();
        }

        if (!$project) {
            return redirect()->route('projects.index')->with('error', 'No projects found. Please create a project first.');
        }

        // Only load real evidence uploaded through the project (exclude mock paths)
        $evidenceFiles = $project->evidenceFiles()
            ->where('file_path', 'not like', 'mock/%')
            ->with(['requirement', 'feedbacks', 'user'])
            ->latest()
            ->get();

        $projects = \App\Models\Project::latest()->get();

        return view('evidence.hub', [
            'project' => $project,
            'evidenceFiles' => $evidenceFiles,
            'projects' => $projects,
        ]);
    }
}
