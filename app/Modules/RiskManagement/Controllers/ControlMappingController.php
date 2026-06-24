<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Models\FrameworkControl;
use App\Models\Control;
use App\Modules\RiskManagement\Models\RiskControlMapping;
use App\Modules\RiskManagement\Services\ControlMappingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ControlMappingController extends Controller
{
    public function __construct(
        private ControlMappingService $mappingService
    ) {}

    /**
     * POST /api/rmm/control-mapping/suggest
     *
     * Accepts either:
     *   { risk_register_id: N }
     *   { query: "free text description" }
     *   { query: "...", framework_id: N }
     *
     * Returns ordered suggestions with confidence scores.
     */
    public function suggest(Request $request)
    {
        $request->validate([
            'risk_register_id' => 'required_without:query|integer|exists:risk_registers,id',
            'query'            => 'required_without:risk_register_id|string|max:2000',
            'framework_id'     => 'nullable|integer|exists:frameworks,id',
            'limit'            => 'nullable|integer|min:1|max:50',
            'include_local'    => 'nullable|boolean',
        ]);

        $source = $request->filled('risk_register_id')
            ? RiskRegister::findOrFail($request->risk_register_id)
            : $request->input('query');

        $limit = $request->integer('limit', 10);
        $frameworkId = $request->integer('framework_id', null) ?: null;

        if ($request->boolean('include_local', false)) {
            $results = $this->mappingService->suggestAll($source, $limit, $frameworkId);
        } else {
            $suggestions = $this->mappingService->suggest($source, $limit, $frameworkId);
            $results = ['framework_controls' => $suggestions];
        }

        $formatted = $this->formatResults($results);

        return response()->json([
            'success' => true,
            'data'    => $formatted,
        ]);
    }

    /**
     * POST /api/rmm/control-mapping/confirm
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'mapping_id' => 'required|integer|exists:risk_control_mappings,id',
        ]);

        $mapping = $this->mappingService->confirmMapping($request->mapping_id);

        return response()->json([
            'success' => true,
            'message' => 'Mapping confirmed.',
            'data'    => $mapping->load(['frameworkControl', 'control']),
        ]);
    }

    /**
     * POST /api/rmm/control-mapping/reject
     */
    public function reject(Request $request)
    {
        $request->validate([
            'mapping_id' => 'required|integer|exists:risk_control_mappings,id',
        ]);

        $mapping = $this->mappingService->rejectMapping($request->mapping_id);

        return response()->json([
            'success' => true,
            'message' => 'Mapping rejected.',
            'data'    => $mapping->load(['frameworkControl', 'control']),
        ]);
    }

    /**
     * POST /api/rmm/control-mapping/manual
     *
     * Manually map a risk to a framework control (and optionally a local control).
     */
    public function manualMap(Request $request)
    {
        $request->validate([
            'risk_register_id'     => 'required|integer|exists:risk_registers,id',
            'framework_control_id' => 'required|integer|exists:framework_controls,id',
            'control_id'           => 'nullable|integer|exists:controls,id',
            'notes'                => 'nullable|string|max:2000',
        ]);

        $mapping = $this->mappingService->manualMap(
            $request->risk_register_id,
            $request->framework_control_id,
            $request->control_id,
            $request->notes
        );

        return response()->json([
            'success' => true,
            'message' => 'Mapping created.',
            'data'    => $mapping->load(['frameworkControl', 'control']),
        ]);
    }

    /**
     * DELETE /api/rmm/control-mapping/{riskRegisterId}/{frameworkControlId}
     */
    public function destroy($riskRegisterId, $frameworkControlId)
    {
        $this->mappingService->unmap((int) $riskRegisterId, (int) $frameworkControlId);

        return response()->json([
            'success' => true,
            'message' => 'Mapping removed.',
        ]);
    }

    /**
     * GET /api/rmm/control-mapping/by-risk/{riskRegisterId}
     *
     * List all mappings for a given risk register entry.
     */
    public function byRisk($riskRegisterId)
    {
        $mappings = RiskControlMapping::with(['frameworkControl', 'control', 'mappedBy'])
            ->where('risk_register_id', $riskRegisterId)
            ->orderByDesc('confidence_score')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $mappings,
        ]);
    }

    /**
     * GET /api/rmm/control-mapping/frameworks
     *
     * List all available framework controls grouped by framework.
     */
    public function frameworks()
    {
        $frameworks = FrameworkControl::with('framework')
            ->get()
            ->groupBy(fn ($fc) => $fc->framework?->name ?? 'Uncategorized');

        return response()->json([
            'success' => true,
            'data'    => $frameworks,
        ]);
    }

    /**
     * GET /api/rmm/control-mapping/local-controls
     *
     * List all local (internal) controls.
     */
    public function localControls()
    {
        $controls = Control::with('controlOwner', 'framework')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $controls,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Internal                                                          */
    /* ------------------------------------------------------------------ */

    private function formatResults(array $results): array
    {
        $formatted = [];

        if (isset($results['framework_controls'])) {
            $formatted['framework_controls'] = $results['framework_controls']->map(fn ($s) => [
                'id'               => $s['framework_control']->id,
                'framework_id'     => $s['framework_control']->framework_id,
                'framework_name'   => $s['framework_control']->framework?->name,
                'control_id'       => $s['framework_control']->control_id,
                'domain'           => $s['framework_control']->domain,
                'description'      => $s['framework_control']->requirement_description,
                'control_name'     => $s['framework_control']->control_name,
                'required_evidence'=> $s['framework_control']->required_evidence,
                'status'           => $s['framework_control']->status,
                'pci_dss_ref'      => $s['framework_control']->pci_dss_ref,
                'iso_ref'          => $s['framework_control']->iso_ref,
                'bb_ict_ref'       => $s['framework_control']->bb_ict_ref,
                'swift_ref'        => $s['framework_control']->swift_ref,
                'confidence_score' => $s['confidence_score'],
                'match_type'       => $s['match_type'] ?? 'fuzzy',
            ]);
        }

        if (isset($results['local_controls'])) {
            $formatted['local_controls'] = $results['local_controls']->map(fn ($s) => [
                'id'               => $s['control']->id,
                'code'             => $s['control']->code ?? $s['control']->control_code,
                'title'            => $s['control']->title ?? $s['control']->name,
                'description'      => $s['control']->description,
                'effectiveness_score' => $s['control']->effectiveness_score,
                'status'           => $s['control']->status,
                'confidence_score' => $s['confidence_score'],
                'match_type'       => 'fuzzy',
            ]);
        }

        return $formatted;
    }
}
