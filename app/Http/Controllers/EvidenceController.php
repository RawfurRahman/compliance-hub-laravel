<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvidenceRequest;
use App\Jobs\AnalyzeEvidenceJob;
use App\Mail\AiAnalysisReport;
use App\Models\AssessmentFinding;
use App\Models\ChatMessage;
use App\Models\EvidenceFile;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\PciDssFinding;
use App\Models\PciDssRequirement;
use App\Models\Project;
use App\Models\ProjectPciDssDetail;
use App\Models\User;
use App\Services\AiGapConsolidationService;
use App\Services\DirectEvidenceAnalysisService;
use App\Services\EvidenceScanService;
use App\Services\EvidenceTrackerService;
use App\Services\GapAssessmentReportService;
use App\Services\UploadRejectionLogger;
use App\Services\ZipExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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

        if (! $timestamp || ! $signature || ! $secret) {
            return false;
        }

        $payload = $timestamp.'.'.($request->header('X-Event-ID') ?? $request->getContent());
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $secret);

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

            // Format for UI. All requirements are always shown in the hub;
            // any marked not-applicable are flagged with the N/A badge instead
            // of being removed from the list.
            $requirementsData = $requirements->map(function ($req) use ($findings) {
                $finding = $findings->get($req->id);
                $majorNum = explode('.', $req->req_num)[0];

                return [
                    'id' => $req->id,
                    'req_num' => $req->req_num,
                    'description' => $req->req_description,
                    'domain' => 'Requirement '.$majorNum,
                    'name' => '',
                    'is_applicable' => ($finding && $finding->is_applicable === false) ? 0 : 1,
                ];
            })->values();
        } else {
            // Non-PCI Framework (e.g. ISO 27001:2022)
            $framework = Framework::where('slug', $project->module_type)->first();
            $controls = $framework ? FrameworkControl::where('framework_id', $framework->id)->get()->sortBy('control_id', SORT_NATURAL) : collect();

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
    public function upload(StoreEvidenceRequest $request, Project $project)
    {
        $isPci = $project->module_type === 'pci_dss';

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
                $auditor = User::whereHas('roles', fn ($q) => $q->where('name', 'Auditor'))->first();
                $auditorEmail = $auditor ? $auditor->email : 'default-auditor@example.com';
                $reviewLink = route('evidence.show', ['project' => $project->id]).'#evidence-file-'.$evidence->id;
                $fileContents = Storage::disk('public')->get($path);

                $reqText = $this->resolveRequirementText($evidence, $isPci);
                $reqNum = $isPci
                    ? (optional($evidence->requirement)->req_num ?? '')
                    : (optional($evidence->frameworkControl)->control_id ?? '');

                $timestamp = time();
                $signature = hash_hmac('sha256', $timestamp.'.'.$evidence->id, env('N8N_WEBHOOK_SECRET', ''));

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
                    ->post($n8nWebhookUrl);

                Log::info("n8n unified evidence processing webhook triggered for evidence_file_id: {$evidence->id}");
                $n8nSuccess = true;
            } catch (\Exception $e) {
                Log::warning('n8n unified trigger failed, falling back to direct analysis: '.$e->getMessage());
            }
        }

        if (! $n8nSuccess) {
            AnalyzeEvidenceJob::dispatch($evidence->id);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded and sent for security scanning and AI analysis.',
                'evidence_id' => $evidence->id,
            ]);
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
        if (! $this->authenticateN8nCallback($request)) {
            Log::warning('Invalid n8n scan-callback signature from IP: '.$request->ip());

            return response()->json(['status' => 'error', 'message' => 'Unauthorized: Invalid signature'], 401);
        }

        $request->validate([
            'evidence_file_id' => 'required|exists:evidence_files,id',
            'scan_status' => 'required|string|in:clean,infected,failed',
            'scan_details' => 'nullable|array',
        ]);

        $evidenceFile = EvidenceFile::find($request->evidence_file_id);
        if (! $evidenceFile) {
            return response()->json(['status' => 'error', 'message' => 'Evidence file not found'], 404);
        }

        $evidenceFile->update([
            'scan_status' => $request->scan_status,
            'scan_details' => $request->scan_details,
        ]);

        Log::info("EvidenceFile ID {$evidenceFile->id} scan status updated to: {$request->scan_status}");

        // Handle Infected Files: Quarantine for Security (never deletes)
        if ($evidenceFile->scan_status === 'infected') {
            $virusName = $request->input('virus_name')
                ?? $request->scan_details['virus'] ?? 'Malware detected by ClamAV scan';

            Log::warning("SECURITY ALERT: Infected file detected. Quarantining EvidenceFile ID {$evidenceFile->id}: {$evidenceFile->file_path}");

            app(EvidenceScanService::class)->quarantine(
                $evidenceFile,
                $virusName,
                $request->scan_details,
            );

            return response()->json([
                'status' => 'security_action_taken',
                'message' => 'Infected file detected and quarantined for security.',
            ]);
        }

        if ($evidenceFile->scan_status === 'clean') {
            // Record the clean scan for dashboard statistics
            app(EvidenceScanService::class)->recordScan(
                $evidenceFile,
                'clean',
                $request->scan_details,
            );

            // Since we are using the Unified Evidence Processing Workflow in n8n,
            // the workflow itself automatically proceeds to the AI Analysis step.
            // We just need to mark the AI status as 'processing'.
            $evidenceFile->update(['ai_analysis_status' => 'processing']);
            Log::info("EvidenceFile ID {$evidenceFile->id} is clean. AI analysis is being processed automatically by n8n.");
        } else {
            $evidenceFile->update(['ai_analysis_status' => 'skipped_due_to_scan']);

            // Record failed scans so dashboard stats stay accurate
            if ($evidenceFile->scan_status === 'failed') {
                app(EvidenceScanService::class)->recordScan(
                    $evidenceFile,
                    'failed',
                    $request->scan_details,
                );
            }
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
        if (! $this->authenticateN8nCallback($request)) {
            Log::warning('Invalid n8n ai-callback signature from IP: '.$request->ip());

            return response()->json(['status' => 'error', 'message' => 'Unauthorized: Invalid signature'], 401);
        }

        $request->validate([
            'evidence_file_id' => 'required|exists:evidence_files,id',
            'observations' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'status' => 'required|string|in:completed,failed',
            'gaps' => 'nullable|array',
            'report_fields' => 'nullable|array',
        ]);

        $evidenceFile = EvidenceFile::find($request->evidence_file_id);
        if (! $evidenceFile) {
            return response()->json(['status' => 'error', 'message' => 'Evidence file not found'], 404);
        }

        // Structured draft fields the n8n Ollama pass proposed (see Parse Response node) —
        // the auditor reviews and edits these in the Evidence Hub before anything is
        // pushed to the Gap Assessment; the AI's output here is never a final verdict.
        $incomingReportFields = $request->input('report_fields', []);
        // Vision models are inconsistent about casing ("process" vs "Process"); a near-miss
        // silently fails to select any <select> option in the review form, so normalize here.
        if (isset($incomingReportFields['risk_rating'])) {
            $incomingReportFields['risk_rating'] = DirectEvidenceAnalysisService::normalizeEnum(
                $incomingReportFields['risk_rating'], ['None', 'Low', 'Medium', 'High']
            );
        }
        if (isset($incomingReportFields['gap_category'])) {
            $incomingReportFields['gap_category'] = DirectEvidenceAnalysisService::normalizeEnum(
                $incomingReportFields['gap_category'], ['Policy', 'Technical', 'Process', 'Organizational', 'Physical']
            );
        }

        $reportFields = array_filter(array_merge($incomingReportFields, [
            'observation' => $request->observations,
            'recommended_action' => $request->recommendations,
            'evidence_provided' => $evidenceFile->original_filename,
        ]), fn ($v) => $v !== null);

        $evidenceFile->update([
            'ai_observations' => $request->observations,
            'ai_recommendations' => $request->recommendations,
            'ai_gaps' => $request->input('gaps', []),
            'ai_analysis_status' => ($request->status === 'completed') ? 'awaiting_review' : 'failed',
            'analysis_report_data' => $reportFields ?: null,
        ]);

        $evidenceFile->fresh()->recordAnalysisVersion('ai_analysis');

        Log::info("EvidenceFile ID {$evidenceFile->id} AI analysis status updated to: {$evidenceFile->ai_analysis_status}");

        $n8nHitlWebhookUrl = env('N8N_HITL_WEBHOOK_URL');
        if ($n8nHitlWebhookUrl && $evidenceFile->ai_analysis_status === 'awaiting_review') {
            try {
                $auditor = User::whereHas('roles', fn ($q) => $q->where('name', 'Auditor'))->first();
                $auditorEmail = $auditor ? $auditor->email : 'default-auditor@example.com';
                $reviewLink = route('evidence.show', ['project' => $evidenceFile->project_id]).'#evidence-file-'.$evidenceFile->id;

                $isPci = optional($evidenceFile->project)->module_type === 'pci_dss';
                $reqNum = $isPci
                    ? (optional($evidenceFile->requirement)->req_num ?? '')
                    : (optional($evidenceFile->frameworkControl)->control_id ?? '');

                $timestamp = time();
                $signature = hash_hmac('sha256', $timestamp.'.'.$evidenceFile->id, env('N8N_WEBHOOK_SECRET', ''));

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
                Log::error('Failed to trigger n8n HITL workflow: '.$e->getMessage(), [
                    'evidence_file_id' => $evidenceFile->id,
                    'exception' => $e,
                ]);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'AI analysis result received.']);
    }

    public function approveAiAnalysis(EvidenceFile $evidenceFile)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
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

        // Auto-consolidate AI gaps into assessment findings
        try {
            app(AiGapConsolidationService::class)->consolidate($evidenceFile);
            $evidenceFile->update(['ai_gaps_consolidated_at' => now()]);
        } catch (\Exception $e) {
            Log::error("AI gap consolidation failed for evidence_file_id {$evidenceFile->id}: ".$e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'AI analysis approved!',
            'assessment_finding' => $evidenceFile->fresh()->assessmentFinding()->with('observations')->first(),
        ]);
    }

    public function rejectAiAnalysis(Request $request, EvidenceFile $evidenceFile)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $rejectionNote = $request->input('note', '');

        // Save the rejection note as feedback if provided
        if (! empty($rejectionNote)) {
            $evidenceFile->feedbacks()->create([
                'user_id' => auth()->id(),
                'message' => '[AI Rejection Note] '.$rejectionNote,
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
                    $fileContents = 'Re-analysis requested for '.$evidenceFile->original_filename;
                }

                $auditor = User::whereHas('roles', fn ($q) => $q->where('name', 'Auditor'))->first();
                $auditorEmail = $auditor ? $auditor->email : 'default-auditor@example.com';
                $reviewLink = route('evidence.show', ['project' => $evidenceFile->project_id]).'#evidence-file-'.$evidenceFile->id;

                $isPci = optional($evidenceFile->project)->module_type === 'pci_dss';
                $reqText = $this->resolveRequirementText($evidenceFile, $isPci);
                $reqNum = $isPci
                    ? (optional($evidenceFile->requirement)->req_num ?? '')
                    : (optional($evidenceFile->frameworkControl)->control_id ?? '');

                $timestamp = time();
                $signature = hash_hmac('sha256', $timestamp.'.'.$evidenceFile->id, env('N8N_WEBHOOK_SECRET', ''));

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
                    ->post($n8nWebhookUrl);

                Log::info("n8n re-analysis webhook triggered for evidence_file_id: {$evidenceFile->id}");
                $n8nSuccess = true;
            } catch (\Exception $e) {
                Log::warning('n8n re-analysis trigger failed, falling back to direct analysis: '.$e->getMessage());
            }
        }

        if ($evidenceFile->hitl_status === 'accepted' || $evidenceFile->ai_analysis_status === 'approved') {
            $this->revertFinding($evidenceFile);
        }

        // Preserve the result being superseded before it is overwritten.
        $evidenceFile->recordAnalysisVersion('reanalysis_requested', auth()->id(), $rejectionNote ?: null);

        $evidenceFile->update([
            'scan_status' => 'pending',
            'ai_analysis_status' => 'processing',
            'ai_observations' => 'Re-analysis in progress...',
            'ai_recommendations' => '',
            'hitl_status' => 'pending_review',
        ]);

        if (! $n8nSuccess) {
            AnalyzeEvidenceJob::dispatch($evidenceFile->id);
        }

        return response()->json(['status' => 'success', 'message' => 'AI analysis rejected and re-triggered.']);
    }

    /**
     * Assign a user to a specific requirement for evidence collection.
     */
    public function assignUser(Request $request, Project $project, $requirement)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $evidenceFile = $project->evidenceFiles()
            ->where(function ($q) use ($requirement) {
                $q->where('pci_dss_requirement_id', $requirement)
                    ->orWhere('framework_control_id', $requirement);
            })
            ->first();

        if (! $evidenceFile) {
            return response()->json(['status' => 'error', 'message' => 'No evidence found for this requirement.'], 404);
        }

        $evidenceFile->update(['user_id' => $request->user_id]);

        return response()->json(['status' => 'success', 'message' => 'User assigned successfully.']);
    }

    /**
     * Get all feedbacks for a given evidence file.
     */
    public function getFeedbacks(EvidenceFile $evidenceFile)
    {
        $feedbacks = $evidenceFile->feedbacks()
            ->with('user')
            ->latest()
            ->get();

        return response()->json($feedbacks);
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
        $messages = ChatMessage::whereNull('read_at')
            ->where('created_at', '<=', Carbon::now()->subMinutes(5))
            ->with('user', 'project.user')
            ->get();

        foreach ($messages as $message) {
            $message->update(['read_at' => now()]);
        }

        return response()->json($messages);
    }

    public function submitFeedback(Request $request, EvidenceFile $evidenceFile)
    {
        $formats = implode(', ', config('uploads.evidence.extensions'));

        UploadRejectionLogger::validate(
            $request,
            [
                'message' => 'required|string',
                'action' => 'required|in:accept,return,reply',
                'file' => [
                    'nullable',
                    'file',
                    'mimes:'.implode(',', config('uploads.evidence.extensions')),
                    'mimetypes:'.implode(',', config('uploads.evidence.mimetypes')),
                    'max:'.(int) config('uploads.evidence.max_size_kb'),
                ],
            ],
            'evidence.upload.rejected',
            [
                'file.mimes' => "The file must be one of the accepted formats: {$formats}.",
                'file.mimetypes' => "The file content does not match the accepted formats. Allowed: {$formats}.",
                'file.max' => 'The file may not be larger than '.((int) config('uploads.evidence.max_size_kb') / 1024).' MB.',
            ],
        );

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
                $filename = time().'_'.$file->getClientOriginalName();
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
    /**
     * Resolve the requirement text to send to n8n/Ollama for a given evidence file.
     * Empty-string descriptions (a real data gap in some seeded framework controls)
     * must not silently fall through as a blank requirement — the model then has
     * nothing concrete to analyze against and tends to default to generic output.
     */
    protected function resolveRequirementText(EvidenceFile $evidenceFile, bool $isPci): string
    {
        $text = $isPci
            ? optional($evidenceFile->requirement)->req_description
            : optional($evidenceFile->frameworkControl)->requirement_description;

        if (! empty($text)) {
            return $text;
        }

        $controlId = $isPci
            ? (optional($evidenceFile->requirement)->req_num ?? 'unknown')
            : (optional($evidenceFile->frameworkControl)->control_id ?? 'unknown');

        return "No requirement description is on file for control {$controlId}. Do not assume what ".
            'the requirement covers. Base your analysis strictly on what the evidence file itself '.
            'demonstrates, and state in your observations that the requirement text was unavailable.';
    }

    protected function autoComplyFinding(EvidenceFile $evidenceFile): void
    {
        if (! $evidenceFile->project_id) {
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
                ? '[Auto-populated from accepted evidence #'.$evidenceFile->id.'] '.$evidenceFile->ai_observations
                : 'Accepted evidence #'.$evidenceFile->id.' demonstrates compliance for this requirement.';

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
        if (! $evidenceFile->project_id) {
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
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            abort(403, 'Unauthorized');
        }
        try {
            $export = $zipService->createEvidencePackage($project);

            return response()->download($export['path']);
        } catch (\Exception $e) {
            Log::error('Evidence ZIP export failed: '.$e->getMessage());

            return back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    /**
     * Toggle the 'is_applicable' (In-Scope vs N/A) status for a requirement.
     */
    public function toggleScope(Request $request, Project $project, $requirement)
    {
        $isPci = $project->module_type === 'pci_dss';
        if (! $isPci) {
            return response()->json(['status' => 'error', 'message' => 'Scope toggle is not supported for agnostic frameworks.'], 400);
        }

        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
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

        $uploads = $uploadsQuery->latest()->take(3)->get()->map(fn ($f) => [
            'type' => 'upload',
            'user' => $f->user->username,
            'req' => $isPci
                ? ($f->requirement ? $f->requirement->req_num : '')
                : ($f->frameworkControl ? $f->frameworkControl->control_id : ''),
            'time' => $f->created_at->diffForHumans(),
            'icon' => 'fa-cloud-upload-alt text-sky-500',
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
            ->map(fn ($f) => [
                'type' => 'comment',
                'user' => $f->username,
                'time' => Carbon::parse($f->created_at)->diffForHumans(),
                'icon' => 'fa-comments text-indigo-500',
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
        $statusLabel = 'Initializing Process...';

        if ($evidenceFile->scan_status === 'pending') {
            $statusLabel = 'Scanning for vulnerabilities (ClamAV)...';
        } elseif ($evidenceFile->scan_status === 'clean' && $evidenceFile->ai_analysis_status === 'pending') {
            $statusLabel = 'Transmitting to AI Analysis Core...';
        } elseif ($evidenceFile->ai_analysis_status === 'processing') {
            $statusLabel = 'AI is analyzing document context...';
        } elseif ($evidenceFile->ai_analysis_status === 'awaiting_review') {
            $statusLabel = 'Awaiting Auditor HITL Validation';
        } elseif ($evidenceFile->hitl_status === 'accepted') {
            $statusLabel = 'Evidence Approved & Locked';
        } elseif ($evidenceFile->ai_analysis_status === 'failed') {
            $statusLabel = 'Analysis Failed — Review Required';
        } elseif ($evidenceFile->scan_status === 'infected') {
            $statusLabel = 'Malicious Content Detected — Quarantined';
        } elseif ($evidenceFile->ai_analysis_status === 'skipped_due_to_scan') {
            $statusLabel = 'Skipped Due to Scan Failure';
        }

        return response()->json([
            'id' => $evidenceFile->id,
            'scan_status' => $evidenceFile->scan_status,
            'ai_analysis_status' => $evidenceFile->ai_analysis_status,
            'hitl_status' => $evidenceFile->hitl_status,
            'status_label' => $statusLabel,
            'ai_observations' => $evidenceFile->ai_observations,
            'ai_recommendations' => $evidenceFile->ai_recommendations,
            'gaps' => $evidenceFile->ai_gaps ?? [],
            // The structured Gap Assessment review form reads this — without it, a
            // re-analysis updates ai_observations/ai_gaps client-side (below) but
            // leaves gap_category/impact_assessment/etc. frozen at page-load values.
            'analysis_report_data' => $evidenceFile->analysis_report_data,
            'ai_approved_by' => optional($evidenceFile->approvedBy)->username,
            'ai_approved_at' => $evidenceFile->ai_analysis_approved_at?->toDateTimeString(),
            'ai_gaps_consolidated_at' => $evidenceFile->ai_gaps_consolidated_at?->toDateTimeString(),
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
            Mail::to($validated['to_email'])->send(new AiAnalysisReport(
                $validated['observations'],
                $validated['recommendations'],
                $validated['file_name']
            ));

            return response()->json(['message' => 'AI analysis email sent successfully']);
        } catch (\Exception $e) {
            Log::error('AI Analysis Email Error: '.$e->getMessage());

            return response()->json(['error' => 'Failed to send email: '.$e->getMessage()], 500);
        }
    }

    /**
     * Download or retrieve the physical evidence file.
     */
    public function getFile($id)
    {
        $evidenceFile = EvidenceFile::findOrFail($id);

        if (! Storage::disk('public')->exists($evidenceFile->file_path)) {
            abort(404, 'File not found');
        }

        return response()->file(Storage::disk('public')->path($evidenceFile->file_path));
    }

    /**
     * Show the flat Evidence Hub page matching the user's dashboard image mockup.
     */
    public function hub(?Project $project = null)
    {
        $projects = Project::latest()->get();

        if (! $project) {
            return view('evidence.hub', [
                'project' => null,
                'evidenceFiles' => collect(),
                'projects' => $projects,
                'frameworkName' => '',
                'provisionOptions' => collect(),
                'hasTrustCenter' => false,
            ]);
        }

        $isPci = $project->module_type === 'pci_dss';
        $frameworkName = 'PCI DSS';
        $relations = ['feedbacks', 'user', 'assessmentFinding.observations'];
        $provisionOptions = collect();

        if ($isPci) {
            $relations[] = 'requirement';
            $provisionOptions = PciDssRequirement::orderBy('req_num')
                ->get(['id', 'req_num', 'req_description'])->values();
        } else {
            $relations[] = 'frameworkControl';
            $framework = Framework::where('slug', $project->module_type)->first();
            if ($framework) {
                $frameworkName = $framework->name;
                $provisionOptions = FrameworkControl::where('framework_id', $framework->id)
                    ->orderBy('control_id')
                    ->get(['id', 'control_id', 'control_name', 'requirement_description'])->values();
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
            'provisionOptions' => $provisionOptions,
            'hasTrustCenter' => false,
        ]);
    }

    public function consolidateGaps(EvidenceFile $evidenceFile)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        if ($evidenceFile->ai_analysis_status !== 'approved') {
            return response()->json(['status' => 'error', 'message' => 'AI analysis must be approved first.'], 400);
        }

        try {
            $findings = app(AiGapConsolidationService::class)->consolidate($evidenceFile);
            $evidenceFile->update(['ai_gaps_consolidated_at' => now()]);

            return response()->json([
                'status' => 'success',
                'message' => count($findings).' AI gap(s) consolidated into assessment findings.',
                'findings_count' => count($findings),
            ]);
        } catch (\Exception $e) {
            Log::error("AI gap consolidation failed for evidence_file_id {$evidenceFile->id}: ".$e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Consolidation failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * ============================================================
     *  EVIDENCE TRACKER WORKFLOW ENDPOINTS
     * ============================================================
     */
    public function trackerDashboard(Project $project)
    {
        $this->authorize('view', $project);
        $dashboard = app(EvidenceTrackerService::class)->getTrackerDashboard($project);

        return view('evidence-tracker.dashboard', $dashboard + ['project' => $project]);
    }

    public function submitForReview(Request $request, EvidenceFile $evidenceFile)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $data = $request->validate(['analysis_report_data' => 'required|array']);
        try {
            $evidence = app(EvidenceTrackerService::class)->submitForReview($evidenceFile, $data['analysis_report_data']);

            return response()->json(['success' => true, 'evidence' => $evidence->fresh()]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function approveWithData(Request $request, EvidenceFile $evidenceFile)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $data = $request->validate(['analysis_report_data' => 'required|array']);
        try {
            $evidence = app(EvidenceTrackerService::class)->approveAnalysis($evidenceFile, $data['analysis_report_data']);

            return response()->json(['success' => true, 'evidence' => $evidence->fresh()]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Full auditor-review workflow for the Evidence Hub: accepts the complete structured
     * gap-assessment field set (status, risk rating, gap/impact/recommendation, compliance
     * category fields, etc.), stores it against the evidence file for auditability, and
     * pushes it straight into the project's Gap Assessment as an AssessmentFinding.
     */
    public function reviewAndSendToGapAssessment(Request $request, EvidenceFile $evidenceFile)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            // Named workflow_status (not status) to avoid clobbering analysis_report_data's
            // 'status' key, which holds the AI's three-class compliance verdict consumed by
            // the evaluation harness (EvaluationRunService::resolveVerdict).
            'workflow_status' => 'nullable|in:Open,In Progress,Closed',
            'risk_rating' => 'nullable|in:None,Low,Medium,High',
            'is_compliant' => 'nullable|boolean',
            'observation' => 'nullable|string|max:5000',
            'gap_description' => 'nullable|string|max:5000',
            'impact_assessment' => 'nullable|string|max:5000',
            'recommended_action' => 'nullable|string|max:5000',
            'due_date' => 'nullable|date',
            'gap_category' => 'nullable|in:Policy,Technical,Process,Organizational,Physical',
            'non_compliant_details' => 'nullable|string|max:5000',
            'compliant_description' => 'nullable|string|max:5000',
            'remediation_plan' => 'nullable|string|max:5000',
            'evidence_provided' => 'nullable|string|max:1000',
            'test_results' => 'nullable|string|max:5000',
            'meets_standard' => 'nullable|boolean',
            'auditor_notes' => 'nullable|string|max:5000',
        ]);

        if (! $evidenceFile->framework_control_id) {
            return response()->json(['status' => 'error', 'message' => 'This evidence file has no linked framework control.'], 400);
        }

        $evidenceFile->update([
            'analysis_report_data' => array_merge($evidenceFile->analysis_report_data ?? [], $validated),
        ]);

        try {
            $finding = app(GapAssessmentReportService::class)->sendToGapAssessment($evidenceFile);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }

        $evidenceFile->update(['gap_assessment_sent_at' => now()]);
        app(EvidenceTrackerService::class)->logWorkflow($evidenceFile, $evidenceFile->tracker_status, $evidenceFile->tracker_status, "Reviewed and pushed to gap assessment (finding_id: {$finding->id})");

        return response()->json([
            'success' => true,
            'message' => 'Analysis pushed to gap assessment.',
            'finding_id' => $finding->id,
            'finding' => $finding->load('frameworkControl'),
        ]);
    }

    public function sendToGapAssessment(EvidenceFile $evidenceFile)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        try {
            $finding = app(EvidenceTrackerService::class)->sendToGapAssessment($evidenceFile);

            return response()->json([
                'success' => true,
                'message' => 'Evidence sent to gap assessment.',
                'finding_id' => $finding->id,
                'finding' => $finding->load('frameworkControl'),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function passToGapAssessment(EvidenceFile $evidenceFile)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        try {
            $result = app(EvidenceTrackerService::class)->passToGapAssessment($evidenceFile);

            return response()->json([
                'success' => true,
                'message' => 'Evidence passed to gap assessment report.',
                'assessment_data' => $result,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function passToFinalReport(EvidenceFile $evidenceFile)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        try {
            $evidence = app(EvidenceTrackerService::class)->passToFinalReport($evidenceFile);

            return response()->json(['success' => true, 'evidence' => $evidence->fresh()]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function autoCreateRisk(EvidenceFile $evidenceFile)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        try {
            $risk = app(EvidenceTrackerService::class)->autoCreateRisk($evidenceFile);

            return response()->json([
                'success' => true,
                'message' => 'Risk auto-created from gap.',
                'risk_id' => $risk->id,
                'risk' => $risk->load('frameworkControl'),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function rejectTrackerReview(Request $request, EvidenceFile $evidenceFile)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $data = $request->validate(['reason' => 'required|string']);
        try {
            $evidence = app(EvidenceTrackerService::class)->rejectAnalysis($evidenceFile, $data['reason']);

            return response()->json(['success' => true, 'evidence' => $evidence->fresh()]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function getWorkflowHistory(EvidenceFile $evidenceFile)
    {
        return response()->json(
            $evidenceFile->workflowLogs()->with('user')->latest()->get()
        );
    }

    public function getAnalysisVersions(EvidenceFile $evidenceFile)
    {
        return response()->json(
            $evidenceFile->analysisVersions()->with('triggeredBy')->get()
        );
    }

    public function bulkSendToGapAssessment(Request $request, Project $project)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $data = $request->validate(['evidence_ids' => 'required|array', 'evidence_ids.*' => 'exists:evidence_files,id']);
        $service = app(EvidenceTrackerService::class);
        $count = 0;
        $errors = 0;
        foreach ($data['evidence_ids'] as $id) {
            $ef = EvidenceFile::find($id);
            if (! $ef || $ef->project_id !== $project->id) {
                continue;
            }
            try {
                $service->sendToGapAssessment($ef);
                $count++;
            } catch (\Exception $e) {
                $errors++;
                Log::warning("Bulk sendToGapAssessment failed for evidence_file_id {$id}: ".$e->getMessage());
            }
        }

        return response()->json(['success' => true, 'sent' => $count, 'errors' => $errors]);
    }

    public function bulkPassToFinalReport(Request $request, Project $project)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $data = $request->validate(['evidence_ids' => 'required|array', 'evidence_ids.*' => 'exists:evidence_files,id']);
        $service = app(EvidenceTrackerService::class);
        $count = 0;
        $errors = 0;
        foreach ($data['evidence_ids'] as $id) {
            $ef = EvidenceFile::find($id);
            if (! $ef || $ef->project_id !== $project->id) {
                continue;
            }
            try {
                $service->passToFinalReport($ef);
                $count++;
            } catch (\Exception $e) {
                $errors++;
                Log::warning("Bulk passToFinalReport failed for evidence_file_id {$id}: ".$e->getMessage());
            }
        }

        return response()->json(['success' => true, 'sent' => $count, 'errors' => $errors]);
    }

    public function bulkAutoCreateRisks(Request $request, Project $project)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }
        $data = $request->validate(['evidence_ids' => 'required|array', 'evidence_ids.*' => 'exists:evidence_files,id']);
        $service = app(EvidenceTrackerService::class);
        $count = 0;
        $errors = 0;
        foreach ($data['evidence_ids'] as $id) {
            $ef = EvidenceFile::find($id);
            if (! $ef || $ef->project_id !== $project->id) {
                continue;
            }
            try {
                $service->autoCreateRisk($ef);
                $count++;
            } catch (\Exception $e) {
                $errors++;
                Log::warning("Bulk autoCreateRisk failed for evidence_file_id {$id}: ".$e->getMessage());
            }
        }

        return response()->json(['success' => true, 'risks_created' => $count, 'errors' => $errors]);
    }

    public function bulkConsolidateGaps(Project $project)
    {
        if (! auth()->user()->hasRole('Auditor') && ! auth()->user()->hasRole('Admin') && ! auth()->user()->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $service = app(AiGapConsolidationService::class);
        $pending = $service->getPendingForProject($project->id);

        if (empty($pending)) {
            return response()->json(['status' => 'success', 'message' => 'No pending AI gaps to consolidate.', 'consolidated' => 0]);
        }

        $totalConsolidated = 0;
        $errors = 0;

        foreach ($pending as $evidenceData) {
            $evidence = EvidenceFile::find($evidenceData['id']);
            if (! $evidence) {
                continue;
            }

            try {
                $findings = $service->consolidate($evidence);
                $evidence->update(['ai_gaps_consolidated_at' => now()]);
                $totalConsolidated += count($findings);
            } catch (\Exception $e) {
                $errors++;
                Log::error("Bulk consolidation failed for evidence_file_id {$evidence->id}: ".$e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Consolidated {$totalConsolidated} gap(s) from approved evidence.".($errors ? " ({$errors} error(s))" : ''),
            'consolidated' => $totalConsolidated,
            'errors' => $errors,
        ]);
    }
}
