<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\FrameworkControl;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Models\Control;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Evidence;
use App\Modules\RiskManagement\Models\RiskComment;
use App\Modules\RiskManagement\Models\RiskAcceptance;
use App\Modules\RiskManagement\Services\RiskRegisterService;
use App\Modules\RiskManagement\Services\RiskCalculationService;
use App\Modules\RiskManagement\Services\ControlMappingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RiskRegisterController extends Controller
{
    public function __construct(
        private RiskRegisterService    $service,
        private RiskCalculationService $calc,
        private ControlMappingService  $mappingService
    ) {}

    /* ================================================================= *
     *  PHASE 3 — Integrated Risk Register (index)
     * ================================================================= */

    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $entries     = $this->service->riskRegisterForProject($project->id);
        $kpis        = $this->service->kpis($project->id);
        $departments = Department::orderBy('name')->get();
        $assets      = Asset::orderBy('name')->get();
        $controls    = FrameworkControl::orderBy('control_id')->get();

        $categories = collect([
            (object) ['id' => 'Cybersecurity', 'name' => 'Cybersecurity'],
            (object) ['id' => 'Compliance', 'name' => 'Compliance'],
            (object) ['id' => 'Operational', 'name' => 'Operational'],
            (object) ['id' => 'Strategic', 'name' => 'Strategic'],
            (object) ['id' => 'Financial', 'name' => 'Financial'],
            (object) ['id' => 'Physical Security', 'name' => 'Physical Security'],
        ]);

        return view('risk-management.register', compact(
            'project', 'entries', 'kpis', 'departments', 'assets', 'controls', 'categories'
        ));
    }

    /* ================================================================= *
     *  PHASE 5 — Heat Map view
     * ================================================================= */

    public function heatmap(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $inherentCells  = $this->service->heatmapCells($project->id, 'inherent');
        $residualCells  = $this->service->heatmapCells($project->id, 'residual');
        $kpis           = $this->service->kpis($project->id);
        $likelihoodAxis = RiskRegister::LIKELIHOOD_AXIS;
        $impactAxis     = RiskRegister::IMPACT_AXIS;

        return view('risk-management.heatmap', compact(
            'project', 'inherentCells', 'residualCells', 'kpis',
            'likelihoodAxis', 'impactAxis'
        ));
    }

    /* ================================================================= *
     *  PHASE 4 — Create / Edit Form
     * ================================================================= */

    public function create(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $departments = Department::orderBy('name')->get();
        $assets      = Asset::orderBy('name')->get();
        $controls    = FrameworkControl::orderBy('control_id')->get();
        $risk        = null;

        $categories = collect([
            (object) ['id' => 'Cybersecurity', 'name' => 'Cybersecurity'],
            (object) ['id' => 'Compliance', 'name' => 'Compliance'],
            (object) ['id' => 'Operational', 'name' => 'Operational'],
            (object) ['id' => 'Strategic', 'name' => 'Strategic'],
            (object) ['id' => 'Financial', 'name' => 'Financial'],
            (object) ['id' => 'Physical Security', 'name' => 'Physical Security'],
        ]);

        return view('risk-management.form', compact(
            'project', 'risk', 'departments', 'assets', 'controls', 'categories'
        ));
    }

    public function edit(Request $request, Project $project, RiskRegister $risk)
    {
        $this->authorize('view', $project);
        abort_if($risk->project_id !== $project->id, 403);

        $risk->load(['controlMappings.frameworkControl', 'comments.user', 'acceptances.requester']);
        
        $departments = Department::orderBy('name')->get();
        $assets      = Asset::orderBy('name')->get();
        $controls    = FrameworkControl::orderBy('control_id')->get();
        $projectEvidence = Evidence::where('project_id', $project->id)->latest()->get();

        $categories = collect([
            (object) ['id' => 'Cybersecurity', 'name' => 'Cybersecurity'],
            (object) ['id' => 'Compliance', 'name' => 'Compliance'],
            (object) ['id' => 'Operational', 'name' => 'Operational'],
            (object) ['id' => 'Strategic', 'name' => 'Strategic'],
            (object) ['id' => 'Financial', 'name' => 'Financial'],
            (object) ['id' => 'Physical Security', 'name' => 'Physical Security'],
        ]);

        return view('risk-management.form', compact(
            'project', 'risk', 'departments', 'assets', 'controls', 'projectEvidence', 'categories'
        ));
    }

    /* ================================================================= *
     *  CRUD — Store
     * ================================================================= */

    public function store(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $data = $request->validate($this->validationRules());
        $data['project_id'] = $project->id;

        $risk = $this->service->upsertEntry($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'risk'    => $risk->load(['frameworkControl']),
                'kpis'    => $this->service->kpis($project->id),
            ]);
        }

        return redirect()
            ->route('risk-register.edit', [$project, $risk])
            ->with('success', "Risk {$risk->serial_no} created successfully.");
    }

    /* ================================================================= *
     *  CRUD — Update (AJAX + form)
     * ================================================================= */

    public function update(Request $request, Project $project, RiskRegister $risk)
    {
        $this->authorize('view', $project);
        abort_if($risk->project_id !== $project->id, 403);

        $data = $request->validate($this->validationRules(update: true));
        $updated = $this->service->upsertEntry($data, $risk->id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'risk'    => $updated->load(['frameworkControl']),
                'kpis'    => $this->service->kpis($project->id),
            ]);
        }

        return back()->with('success', "Risk {$risk->serial_no} updated.");
    }

    /* ================================================================= *
     *  CRUD — Destroy
     * ================================================================= */

    public function destroy(Request $request, Project $project, RiskRegister $risk)
    {
        $this->authorize('view', $project);
        abort_if($risk->project_id !== $project->id, 403);
        $serialNo = $risk->serial_no;
        $this->service->deleteEntry($risk->id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'kpis' => $this->service->kpis($project->id)]);
        }
        return redirect()->route('risk-register.index', $project)->with('success', "Risk {$serialNo} deleted.");
    }

    /* ================================================================= *
     *  PHASE 9 — Workflow: Status Transition
     * ================================================================= */

    public function transition(Request $request, Project $project, RiskRegister $risk)
    {
        $this->authorize('view', $project);
        $request->validate([
            'status' => 'required|in:Not Started,Pending,In Progress,Completed',
            'reason' => 'nullable|string|max:500',
        ]);

        $this->service->transitionStatus($risk, $request->status, $request->reason);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => $request->status]);
        }
        return back()->with('success', "Risk implementation status updated to {$request->status}.");
    }

    /* ================================================================= *
     *  Comments
     * ================================================================= */

    public function addComment(Request $request, Project $project, RiskRegister $risk)
    {
        $request->validate(['body' => 'required|string|max:2000']);

        $comment = $risk->comments()->create([
            'user_id'     => Auth::id(),
            'body'        => $request->body,
        ]);

        return response()->json(['success' => true, 'comment' => $comment->load('user')]);
    }

    /* ================================================================= *
     *  GRC Mappings (Phase 7)
     * ================================================================= */

    public function mapControl(Request $request, Project $project, RiskRegister $risk)
    {
        $request->validate([
            'framework_control_id' => 'required|exists:framework_controls,id',
            'control_id' => 'nullable|exists:controls,id',
            'notes' => 'nullable|string',
        ]);

        $this->mappingService->manualMap(
            $risk->id,
            $request->framework_control_id,
            $request->control_id,
            $request->notes
        );

        $mappings = $this->loadMappings($risk);

        return response()->json(['success' => true, 'mappings' => $mappings]);
    }

    public function unmapControl(Request $request, Project $project, RiskRegister $risk, $frameworkControlId)
    {
        $this->mappingService->unmap($risk->id, (int) $frameworkControlId);

        $mappings = $this->loadMappings($risk);

        return response()->json(['success' => true, 'mappings' => $mappings]);
    }

    /**
     * POST /risk-register/{risk}/suggest-mappings
     *
     * AI/fuzzy suggestions for a given risk entry.
     */
    public function suggestMappings(Request $request, Project $project, RiskRegister $risk)
    {
        $this->authorize('view', $project);

        $limit = $request->integer('limit', 10);
        $frameworkId = $request->integer('framework_id', null) ?: null;

        $suggestions = $this->mappingService->suggest($risk, $limit, $frameworkId);

        // Persist each suggestion as a pending mapping
        foreach ($suggestions as $s) {
            $this->mappingService->createSuggestion(
                $risk->id,
                $s['framework_control']->id,
                null,
                $s['confidence_score']
            );
        }

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions->map(fn($s) => [
                'id'               => $s['framework_control']->id,
                'framework'        => $s['framework_control']->framework?->name,
                'control_id'       => $s['framework_control']->control_id,
                'domain'           => $s['framework_control']->domain,
                'description'      => $s['framework_control']->requirement_description,
                'control_name'     => $s['framework_control']->control_name,
                'confidence_score' => $s['confidence_score'],
            ]),
        ]);
    }

    /**
     * POST /risk-register/{risk}/accept-suggestion/{mapping}
     */
    public function acceptSuggestion(Request $request, Project $project, RiskRegister $risk, \App\Modules\RiskManagement\Models\RiskControlMapping $mapping)
    {
        $this->authorize('view', $project);
        abort_if($mapping->risk_register_id !== $risk->id, 403);

        $this->mappingService->confirmMapping($mapping->id);

        return response()->json(['success' => true, 'mapping' => $mapping->fresh()->load('frameworkControl')]);
    }

    /**
     * POST /risk-register/{risk}/reject-suggestion/{mapping}
     */
    public function rejectSuggestion(Request $request, Project $project, RiskRegister $risk, \App\Modules\RiskManagement\Models\RiskControlMapping $mapping)
    {
        $this->authorize('view', $project);
        abort_if($mapping->risk_register_id !== $risk->id, 403);

        $this->mappingService->rejectMapping($mapping->id);

        return response()->json(['success' => true]);
    }

    /* ================================================================= *
     *  PHASE 11 — PDF Export
     * ================================================================= */

    public function exportPdf(Project $project)
    {
        $this->authorize('view', $project);
        $entries = $this->service->exportRegisterData($project->id);
        $kpis    = $this->service->kpis($project->id);

        $pdf = Pdf::loadView('risk-management.register-pdf', compact('project', 'entries', 'kpis'))
            ->setPaper('a3', 'landscape');

        return $pdf->download(sprintf(
            'Risk-Register-%s-%s.pdf',
            Str::slug($project->name),
            now()->format('Y-m-d')
        ));
    }

    /* ================================================================= *
     *  Excel / CSV Export
     * ================================================================= */

    public function exportExcel(Project $project)
    {
        $this->authorize('view', $project);
        $entries = $this->service->exportRegisterData($project->id);

        $filename = 'Risk-Register-' . Str::slug($project->name) . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                '#', 'Asset / Process / Service', 'Risk Owner', 'Date', 'Asset Value (BDT)',
                'Threat', 'Threat Level (T)', 'Vulnerability', 'Impact C', 'Impact I', 'Impact A',
                'Existing Control', 'Vuln. Level (AV)', 'TV (T+AV)', 'Likelihood (LH)',
                'Risk Rating (AV*TV*LH)', 'Measurement', 'Proposed Control', 'Communication',
                'Impl. From', 'Impl. To', 'Impl. Status', 'Residual TV', 'Residual LH', 'Residual Rating', 'Follow-up Note'
            ]);
            foreach ($entries as $r) {
                fputcsv($out, [
                    $r->serial_no,
                    $r->asset_process_service,
                    $r->risk_owner,
                    $r->risk_calculation_date?->format('Y-m-d'),
                    $r->asset_value_bdt,
                    implode(', ', (array) $r->threats),
                    $r->threat_level_t,
                    implode(', ', (array) $r->vulnerabilities),
                    $r->impact_confidentiality,
                    $r->impact_integrity,
                    $r->impact_availability,
                    $r->existing_control,
                    $r->vulnerability_level_av,
                    $r->tv_t_av,
                    $r->likelihood_lh,
                    $r->risk_rating_avtvlh,
                    $r->measurement,
                    $r->proposed_control,
                    $r->communication,
                    $r->implementation_from?->format('Y-m-d'),
                    $r->implementation_to?->format('Y-m-d'),
                    $r->implementation_status,
                    $r->residual_tv,
                    $r->residual_lh,
                    $r->residual_rating,
                    $r->follow_up_note
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /* ================================================================= *
     *  Score calculator endpoint (Phase 6 — live score preview)
     * ================================================================= */

    public function calculateScore(Request $request)
    {
        $request->validate([
            'threat_level_t'         => 'required|integer|between:1,5',
            'vulnerability_level_av' => 'required|integer|between:1,5',
            'likelihood_lh'          => 'required|integer|between:1,5',
            'residual_tv'            => 'required|integer|between:1,5',
            'residual_lh'            => 'required|integer|between:1,5',
        ]);

        $tv = $this->calc->tvScore($request->threat_level_t, $request->vulnerability_level_av);
        $inherent = $this->calc->inherentScore($request->vulnerability_level_av, $tv, $request->likelihood_lh);
        $residual = $this->calc->residualScore($request->residual_tv, $request->residual_lh);

        return response()->json([
            'tv_t_av'             => $tv,
            'inherent_score'      => $inherent,
            'inherent_risk_level' => RiskRegister::scoreToLevel($inherent),
            'residual_score'      => $residual,
            'residual_risk_level' => RiskRegister::scoreToLevel($residual),
        ]);
    }

    /* ================================================================= *
     *  Helper: validation rules (used by store + update)
     * ================================================================= */

    /**
     * Shared helper to load mappings with their relationships.
     */
    private function loadMappings(RiskRegister $risk): \Illuminate\Support\Collection
    {
        return $risk->controlMappings()
            ->with(['frameworkControl.framework', 'control', 'mappedBy'])
            ->get()
            ->map(fn ($cm) => [
                'id'                 => $cm->id,
                'framework_control_id' => $cm->framework_control_id,
                'control_id'          => $cm->control_id,
                'control_ref'        => $cm->frameworkControl?->control_id ?? 'N/A',
                'control_name'       => $cm->frameworkControl?->control_name ?? ($cm->frameworkControl?->domain ?? 'N/A'),
                'framework_name'     => $cm->frameworkControl?->framework?->name ?? '',
                'local_control_code' => $cm->control?->code ?? $cm->control?->control_code ?? '',
                'local_control_name' => $cm->control?->title ?? $cm->control?->name ?? '',
                'mapping_status'     => $cm->mapping_status,
                'confidence_score'   => $cm->confidence_score,
                'effectiveness'      => $cm->effectiveness,
                'control_type'       => $cm->control_type,
                'notes'              => $cm->notes,
                'mapped_by'          => $cm->mappedBy?->name ?? $cm->mappedBy?->email ?? '',
                'mapped_at'          => $cm->mapped_at?->toISOString(),
            ]);
    }

    private function validationRules(bool $update = false): array
    {
        $sometimes = $update ? 'sometimes|' : '';

        return [
            'serial_no'              => "{$sometimes}required|string|max:50",
            'asset_process_service'  => "{$sometimes}required|string|max:255",
            'risk_owner'             => "{$sometimes}required|string|max:120",
            'risk_calculation_date'  => "{$sometimes}required|date",
            'asset_value_bdt'        => "{$sometimes}required|numeric|min:0",
            'threats'                => 'sometimes|string',
            'threat_level_t'         => 'sometimes|integer|between:1,5',
            'vulnerabilities'        => 'sometimes|string',
            'impact_confidentiality' => 'sometimes|integer|between:1,5',
            'impact_integrity'       => 'sometimes|integer|between:1,5',
            'impact_availability'    => 'sometimes|integer|between:1,5',
            'existing_control'       => 'nullable|string',
            'vulnerability_level_av' => 'sometimes|integer|between:1,5',
            'likelihood_lh'          => "{$sometimes}required|integer|between:1,5",
            'measurement'            => 'sometimes|in:Accepted,Not Accepted',
            'proposed_control'       => 'nullable|string',
            'communication'          => 'nullable|string',
            'implementation_from'    => 'nullable|date',
            'implementation_to'      => 'nullable|date',
            'implementation_status'  => 'sometimes|in:Not Started,Pending,In Progress,Completed',
            'residual_tv'            => "{$sometimes}required|integer|between:1,5",
            'residual_lh'            => "{$sometimes}required|integer|between:1,5",
            'follow_up_note'         => 'nullable|string',
            'category'               => 'sometimes|string|max:100',
            'department'             => 'sometimes|string|max:120',
            'owner_user_id'          => 'nullable|exists:users,id',
            'asset_id'               => 'nullable|exists:assets,id',
            'custom_fields'          => 'nullable|string',
        ];
    }

    public function transitionLifecycle(Request $request, Project $project, RiskRegister $risk)
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', RiskRegister::LIFECYCLE_STATUSES),
            'reason' => 'nullable|string|max:500',
        ]);

        $risk = $this->service->transitionLifecycle($risk, $data['status'], $data['reason'] ?? null);

        if ($request->expectsJson()) {
            return response()->json(['data' => $risk]);
        }

        return redirect()->back()->with('success', "Risk lifecycle updated to {$data['status']}.");
    }

    public function transitionLifecycleApi(Request $request, RiskRegister $risk)
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', RiskRegister::LIFECYCLE_STATUSES),
            'reason' => 'nullable|string|max:500',
        ]);

        $risk = $this->service->transitionLifecycle($risk, $data['status'], $data['reason'] ?? null);

        return response()->json(['data' => $risk]);
    }
}
