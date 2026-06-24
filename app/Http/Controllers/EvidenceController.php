<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\PciDssRequirement;
use App\Models\PciDssFinding;
use App\Models\ProjectPciDssDetail;
use App\Models\AssessmentFinding;
use App\Models\EvidenceFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\ZipExportService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use App\Jobs\AnalyzeEvidenceJob;

class EvidenceController extends Controller
{
    /**
     * Verify n8n webhook signature
     */
    private function verifyN8nSignature(Request $request): bool
    {
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Hub-Signature');
        $secret = env('N8N_WEBHOOK_SECRET');

        if (!$timestamp || !$signature || !$secret) {
            return false;
        }

        $payload = $timestamp . '.' . ($request->header('X-Event-ID') ?? $request->getContent());
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($signature, $expectedSignature);
    }

    /**
     * Authenticate n8n callbacks using API key (preferred) or HMAC signature.
     */
    private function authenticateN8nCallback(Request $request): bool
    {
        // API key check (primary method — n8n sends this easily)
        $n8nApiKey = $request->header('X-N8n-Api-Key') ?? $request->query('api_key');
        $expectedApiKey = env('N8N_API_KEY');

        if ($expectedApiKey && $n8nApiKey === $expectedApiKey) {
            return true;
        }

        // Fall back to HMAC signature verification
        if ($this->verifyN8nSignature($request)) {
            return true;
        }

        return false;
    }

    /**
     * Display the evidence management page for a specific project.
     */
    public function show(Project $project)
    {
        $isPci = $project->module_type === 'pci_dss';
        if ($isPci) {
            $requirements = PciDssRequirement::all()->sortBy('req_num', SORT_NATURAL);
            $project->load('evidenceFiles.user', 'evidenceFiles.approvedBy', 'chatMessages.user.roles', 'pciDssDetails.findings');
            $evidenceByRequirement = $project->evidenceFiles->groupBy('pci_dss_requirement_id');

            $findings = $project->pciDssDetails ? $project->pciDssDetails->findings->keyBy('pci_dss_requirement_id') : collect();

            // Filter out non-applicable requirements for everyone in the Evidence Hub.
            $requirements = $requirements->filter(function ($req) use ($findings) {
                $finding = $findings->get($req->id);
                return !($finding && $finding->is_applicable === false);
            });

            // Format for UI
            $requirementsData = $requirements->map(function ($req) use ($findings) {
                $finding = $findings->get($req->id);
                $majorNum = explode('.', $req->req_num)[0];
                return [
                    'id' => $req->id,
                    'req_num' => $req->req_num,
                    'description' => $req->req_description,
                    'domain' => 'Requirement ' . $majorNum,
                    'name' => '',
                    'is_applicable' => ($finding && $finding->is_applicable === false) ? 0 : 1,
                ];
            })->values();
        } else {
            // Non-PCI Framework (e.g. ISO 27001:2022)
            $framework = \App\Models\Framework::where('slug', $project->module_type)->first();
            $controls = $framework ? \App\Models\FrameworkControl::where('framework_id', $framework->id)->get()->sortBy('control_id', SORT_NATURAL) : collect();
            
            $project->load('evidenceFiles.user', 'evidenceFiles.approvedBy', 'chatMessages.user.roles');
            $evidenceByRequirement = $project->evidenceFiles->groupBy('framework_control_id');
            
            // Map requirements for Alpine
            $requirementsData = $controls->map(function ($control) {
                $name = $control->control_name;
                return [
                    'id' => $control->id,
                    'req_num' => $control->control_id,
                    'description' => $control->requirement_description,
                    'domain' => $control->domain,
                    'name' => $name,
                    'is_applicable' => 1, // Framework controls are in scope by default
                ];
            })->values();
        }

        $domains = $requirementsData->pluck('domain')->unique()->values();

        return view('evidence.show', [
            'project' => $project,
            'requirements' => $requirementsData,
            'evidenceByRequirement' => $evidenceByRequirement,
            'chatMessages' => $project->chatMessages,
            'isPci' => $isPci,
            'domains' => $domains,
        ]);
    }



    /**
     * Handle the file upload process and trigger the first n8n workflow.
     * **MODIFIED FOR MULTIPART FILE UPLOAD**
     */
    public function upload(Request $request, Project $project)
    {
        $isPci = $project->module_type === 'pci_dss';
        
        $rules = [
            'file' => 'required|file|max:20480', // Max 20MB
        ];
        if ($isPci) {
            $rules['requirement_id'] = 'required|exists:pci_dss_requirements,id';
        } else {
            $rules['requirement_id'] = 'required|exists:framework_controls,id';
        }
        
        $request->validate($rules);

        $file = $request->file('file');
        $path = $file->store("evidence/{$project->id}", 'public');

        $data = [
            'user_id' => auth()->id(),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'scan_status' => 'pending',
            'ai_analysis_status' => 'pending',
        ];

        if ($isPci) {
            $data['pci_dss_requirement_id'] = $request->requirement_id;
        } else {
            $data['framework_control_id'] = $request->requirement_id;
        }

        $evidence = $project->evidenceFiles()->create($data);

        $n8nWebhookUrl = env('N8N_UNIFIED_WEBHOOK_URL', '');
        $n8nEnabled = env('N8N_ENABLED', false);
        $n8nSuccess = false;

        if ($n8nEnabled && $n8nWebhookUrl) {
            try {
                $auditor = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Auditor'))->first();
                $auditorEmail = $auditor ? $auditor->email : 'default-auditor@example.com';
                $reviewLink = route('evidence.show', ['project' => $project->id]) . '#evidence-file-' . $evidence->id;
                $fileContents = Storage::disk('public')->get($path);

                $reqText = $isPci 
                    ? (optional($evidence->requirement)->req_description ?? '') 
                    : (optional($evidence->frameworkControl)->requirement_description ?? '');
                $reqNum = $isPci 
                    ? (optional($evidence->requirement)->req_num ?? '') 
                    : (optional($evidence->frameworkControl)->control_id ?? '');

                $timestamp = time();
                $signature = hash_hmac('sha256', $timestamp . '.' . $evidence->id, env('N8N_WEBHOOK_SECRET', ''));

                Http::timeout(5)->retry(0)
                    ->withHeaders([
                        'X-Timestamp' => $timestamp,
                        'X-Hub-Signature' => $signature,
                    ])
                    ->attach(
                        'file',
                        $fileContents,
                        $evidence->original_filename
                    )
                    ->attach('file_base64', base64_encode($fileContents))
                    ->attach('mime_type', $evidence->mime_type)
                    ->attach('requirement_text', $reqText)
                    ->attach('evidence_file_id', (string) $evidence->id)
                    ->attach('project_name', $project->name)
                    ->attach('requirement_num', $reqNum)
                    ->attach('original_filename', $evidence->original_filename)
                    ->attach('auditor_email', $auditorEmail)
                    ->attach('review_link', $reviewLink)
                    ->attach('gemini_api_key', env('GEMINI_API_KEY', ''))
                    ->post($n8nWebhookUrl);

                Log::info("n8n unified evidence processing webhook triggered for evidence_file_id: {$evidence->id}");
                $n8nSuccess = true;
            } catch (\Exception $e) {
                Log::warning('n8n unified trigger failed, falling back to direct analysis: ' . $e->getMessage());
            }
        }

        if (!$n8nSuccess) {
            AnalyzeEvidenceJob::dispatch($evidence->id);
        }

        return back()->with('success', 'File uploaded and sent for security scanning and AI analysis.');
    }

    /**
     * n8n Callback 1: Receive File Security Scan results.
     */
    public function n8nFileScanCallback(Request $request)
    {
        Log::info('n8n scan-callback received', [
            'all' => $request->all(),
            'content' => $request->getContent(),
            'accept' => $request->header('Accept'),
            'ip' => $request->ip(),
        ]);

        // Authenticate: API key (preferred) or HMAC signature
        if (!$this->authenticateN8nCallback($request)) {
            Log::warning('Invalid n8n scan-callback signature from IP: ' . $request->ip());
            return response()->json(['status' => 'error', 'message' => 'Unauthorized: Invalid signature'], 401);
        }

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
            // Since we are using the Unified Evidence Processing Workflow in n8n,
            // the workflow itself automatically proceeds to the Gemini AI Analysis step.
            // We just need to mark the AI status as 'processing'.
            $evidenceFile->update(['ai_analysis_status' => 'processing']);
            Log::info("EvidenceFile ID {$evidenceFile->id} is clean. AI analysis is being processed automatically by n8n.");
        } else {
            $evidenceFile->update(['ai_analysis_status' => 'skipped_due_to_scan']);
        }

        return response()->json(['status' => 'success', 'message' => 'Scan result received.']);
    }

    // ... The rest of the controller methods (AI callback, approval, chat, etc.) remain the same ...
    public function n8nAiAnalysisCallback(Request $request)
    {
        Log::info('n8n ai-callback received', [
            'all' => $request->all(),
            'content' => $request->getContent(),
            'accept' => $request->header('Accept'),
            'ip' => $request->ip(),
        ]);

        // Authenticate: API key (preferred) or HMAC signature
        if (!$this->authenticateN8nCallback($request)) {
            Log::warning('Invalid n8n ai-callback signature from IP: ' . $request->ip());
            return response()->json(['status' => 'error', 'message' => 'Unauthorized: Invalid signature'], 401);
        }

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
                $auditorEmail = $auditor ? $auditor->email : 'default-auditor@example.com';
                $reviewLink = route('evidence.show', ['project' => $evidenceFile->project_id]) . '#evidence-file-' . $evidenceFile->id;

                $isPci = optional($evidenceFile->project)->module_type === 'pci_dss';
                $reqNum = $isPci 
                    ? (optional($evidenceFile->requirement)->req_num ?? '') 
                    : (optional($evidenceFile->frameworkControl)->control_id ?? '');

                $timestamp = time();
                $signature = hash_hmac('sha256', $timestamp . '.' . $evidenceFile->id, env('N8N_WEBHOOK_SECRET', ''));

                Http::withHeaders([
                    'X-Timestamp' => $timestamp,
                    'X-Hub-Signature' => $signature,
                ])->post($n8nHitlWebhookUrl, [
                    'evidence_file_id' => $evidenceFile->id,
                    'file_name' => $evidenceFile->original_filename,
                    'project_name' => optional($evidenceFile->project)->name,
                    'requirement_num' => $reqNum,
                    'auditor_email' => $auditorEmail,
                    'review_link' => $reviewLink,
                ]);
                Log::info("n8n HITL webhook triggered for evidence_file_id: {$evidenceFile->id}");
            } catch (\Exception $e) {
                Log::error('Failed to trigger n8n HITL workflow: ' . $e->getMessage(), [
                    'evidence_file_id' => $evidenceFile->id,
                    'exception' => $e
                ]);
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

        if ($evidenceFile->hitl_status === 'accepted') {
            $this->autoComplyFinding($evidenceFile);
        }

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

        $n8nWebhookUrl = env('N8N_UNIFIED_WEBHOOK_URL', '');
        $n8nEnabled = env('N8N_ENABLED', false);
        $n8nSuccess = false;

        if ($n8nEnabled && $n8nWebhookUrl) {
            try {
                $fileContents = '';
                if (Storage::disk('public')->exists($evidenceFile->file_path)) {
                    $fileContents = Storage::disk('public')->get($evidenceFile->file_path);
                } else {
                    $fileContents = 'Re-analysis requested for ' . $evidenceFile->original_filename;
                }

                $auditor = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Auditor'))->first();
                $auditorEmail = $auditor ? $auditor->email : 'default-auditor@example.com';
                $reviewLink = route('evidence.show', ['project' => $evidenceFile->project_id]) . '#evidence-file-' . $evidenceFile->id;

                $isPci = optional($evidenceFile->project)->module_type === 'pci_dss';
                $reqText = $isPci 
                    ? (optional($evidenceFile->requirement)->req_description ?? '') 
                    : (optional($evidenceFile->frameworkControl)->requirement_description ?? '');
                $reqNum = $isPci 
                    ? (optional($evidenceFile->requirement)->req_num ?? '') 
                    : (optional($evidenceFile->frameworkControl)->control_id ?? '');

                $timestamp = time();
                $signature = hash_hmac('sha256', $timestamp . '.' . $evidenceFile->id, env('N8N_WEBHOOK_SECRET', ''));

                Http::timeout(5)->retry(0)
                    ->withHeaders([
                        'X-Timestamp' => $timestamp,
                        'X-Hub-Signature' => $signature,
                    ])
                    ->attach(
                        'file',
                        $fileContents,
                        $evidenceFile->original_filename
                    )
                    ->attach('file_base64', base64_encode($fileContents))
                    ->attach('mime_type', $evidenceFile->mime_type ?? 'application/octet-stream')
                    ->attach('requirement_text', $reqText)
                    ->attach('evidence_file_id', (string) $evidenceFile->id)
                    ->attach('project_name', optional($evidenceFile->project)->name ?? '')
                    ->attach('requirement_num', $reqNum)
                    ->attach('original_filename', $evidenceFile->original_filename)
                    ->attach('auditor_email', $auditorEmail)
                    ->attach('review_link', $reviewLink)
                    ->attach('gemini_api_key', env('GEMINI_API_KEY', ''))
                    ->post($n8nWebhookUrl);

                Log::info("n8n re-analysis webhook triggered for evidence_file_id: {$evidenceFile->id}");
                $n8nSuccess = true;
            } catch (\Exception $e) {
                Log::warning('n8n re-analysis trigger failed, falling back to direct analysis: ' . $e->getMessage());
            }
        }

        if ($evidenceFile->hitl_status === 'accepted' || $evidenceFile->ai_analysis_status === 'approved') {
            $this->revertFinding($evidenceFile);
        }

        $evidenceFile->update([
            'scan_status' => 'pending',
            'ai_analysis_status' => 'processing',
            'ai_observations' => 'Re-analysis in progress...',
            'ai_recommendations' => '',
            'hitl_status' => 'pending_review',
        ]);

        if (!$n8nSuccess) {
            AnalyzeEvidenceJob::dispatch($evidenceFile->id);
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
            if ($evidenceFile->ai_analysis_status === 'approved') {
                $this->autoComplyFinding($evidenceFile);
            }
        } elseif ($request->action === 'return') {
            if ($evidenceFile->hitl_status === 'accepted') {
                $this->revertFinding($evidenceFile);
            }
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
     * When evidence is both HITL-accepted and AI-approved, auto-mark the linked
     * assessment finding as compliant so it appears as "Compliant" in reports.
     *
     * Two paths:
     * 1. framework_control_id  → marks AssessmentFinding.is_compliant = true (Unified Gap)
     * 2. pci_dss_requirement_id → creates/updates PciDssFinding as 'In Place' (ROC)
     */
    protected function autoComplyFinding(EvidenceFile $evidenceFile): void
    {
        if (!$evidenceFile->project_id) {
            return;
        }

        // Path 1: Unified Gap Assessment
        if ($evidenceFile->framework_control_id) {
            AssessmentFinding::where('framework_control_id', $evidenceFile->framework_control_id)
                ->whereHas('projectAssessment', function ($q) use ($evidenceFile) {
                    $q->where('project_id', $evidenceFile->project_id);
                })
                ->where('is_compliant', false)
                ->update(['is_compliant' => true]);
        }

        // Path 2: PCI DSS → ROC
        if ($evidenceFile->pci_dss_requirement_id) {
            $detail = ProjectPciDssDetail::firstOrCreate(
                ['project_id' => $evidenceFile->project_id],
                [
                    'entity_name' => $evidenceFile->project->name,
                    'assessment_date' => now(),
                ]
            );

            $findingDescription = $evidenceFile->ai_observations
                ? '[Auto-populated from accepted evidence #' . $evidenceFile->id . '] ' . $evidenceFile->ai_observations
                : 'Accepted evidence #' . $evidenceFile->id . ' demonstrates compliance for this requirement.';

            PciDssFinding::updateOrCreate(
                [
                    'project_pci_dss_detail_id' => $detail->id,
                    'pci_dss_requirement_id' => $evidenceFile->pci_dss_requirement_id,
                ],
                [
                    'assessment_finding' => 'In Place',
                    'finding_description' => $findingDescription,
                ]
            );

            Log::info("ROC finding auto-set to 'In Place' for PCI DSS req #{$evidenceFile->pci_dss_requirement_id} from evidence_file_id: {$evidenceFile->id}");
        }
    }

    /**
     * When evidence is un-accepted (returned or rejected), revert the linked
     * findings so reports reflect the current state.
     */
    protected function revertFinding(EvidenceFile $evidenceFile): void
    {
        if (!$evidenceFile->project_id) {
            return;
        }

        // Path 1: Unified Gap Assessment
        if ($evidenceFile->framework_control_id) {
            AssessmentFinding::where('framework_control_id', $evidenceFile->framework_control_id)
                ->whereHas('projectAssessment', function ($q) use ($evidenceFile) {
                    $q->where('project_id', $evidenceFile->project_id);
                })
                ->where('is_compliant', true)
                ->update(['is_compliant' => false]);
        }

        // Path 2: PCI DSS → ROC
        if ($evidenceFile->pci_dss_requirement_id) {
            $detail = ProjectPciDssDetail::where('project_id', $evidenceFile->project_id)->first();
            if ($detail) {
                PciDssFinding::where('project_pci_dss_detail_id', $detail->id)
                    ->where('pci_dss_requirement_id', $evidenceFile->pci_dss_requirement_id)
                    ->where('assessment_finding', 'In Place')
                    ->update(['assessment_finding' => 'Not Tested']);
            }
        }
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
    public function toggleScope(Request $request, Project $project, $requirement)
    {
        $isPci = $project->module_type === 'pci_dss';
        if (!$isPci) {
            return response()->json(['status' => 'error', 'message' => 'Scope toggle is not supported for agnostic frameworks.'], 400);
        }
        
        if (!auth()->user()->hasRole('Auditor') && !auth()->user()->hasRole('Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $request->validate(['is_applicable' => 'required|boolean']);

        $pciReq = PciDssRequirement::findOrFail($requirement);

        if ($project->pciDssDetails) {
            $project->pciDssDetails->findings()->updateOrCreate(
                ['pci_dss_requirement_id' => $pciReq->id],
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
        $isPci = $project->module_type === 'pci_dss';

        // 1. Latest Uploads
        $uploadsQuery = $project->evidenceFiles();
        if ($isPci) {
            $uploadsQuery->with('user', 'requirement');
        } else {
            $uploadsQuery->with('user', 'frameworkControl');
        }

        $uploads = $uploadsQuery->latest()->take(3)->get()->map(fn($f) => [
            'type' => 'upload',
            'user' => $f->user->username,
            'req'  => $isPci 
                ? ($f->requirement ? $f->requirement->req_num : '') 
                : ($f->frameworkControl ? $f->frameworkControl->control_id : ''),
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
        } elseif ($evidenceFile->ai_analysis_status === 'failed') {
            $statusLabel = "Analysis Failed — Review Required";
        } elseif ($evidenceFile->scan_status === 'infected') {
            $statusLabel = "Malicious Content Detected — Quarantined";
        } elseif ($evidenceFile->ai_analysis_status === 'skipped_due_to_scan') {
            $statusLabel = "Skipped Due to Scan Failure";
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
        $projects = \App\Models\Project::latest()->get();

        if (!$project) {
            return view('evidence.hub', [
                'project' => null,
                'evidenceFiles' => collect(),
                'projects' => $projects,
                'frameworkName' => '',
            ]);
        }

        $isPci = $project->module_type === 'pci_dss';
        $frameworkName = 'PCI DSS';
        $relations = ['feedbacks', 'user'];

        if ($isPci) {
            $relations[] = 'requirement';
        } else {
            $relations[] = 'frameworkControl';
            $framework = \App\Models\Framework::where('slug', $project->module_type)->first();
            if ($framework) {
                $frameworkName = $framework->name;
            }
        }

        // Only load real evidence uploaded through the project (exclude mock paths)
        $evidenceFiles = $project->evidenceFiles()
            ->where('file_path', 'not like', 'mock/%')
            ->with($relations)
            ->latest()
            ->get();

        return view('evidence.hub', [
            'project' => $project,
            'evidenceFiles' => $evidenceFiles,
            'projects' => $projects,
            'frameworkName' => $frameworkName,
        ]);
    }
}

