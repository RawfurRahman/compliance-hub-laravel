<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RiskManagement\Exports\ControlMappingSheetExport;
use App\Modules\RiskManagement\Models\RiskControlMapping;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Services\ControlMappingService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ControlMappingDashboardController extends Controller
{
    public function __construct(
        private ControlMappingService $mappingService
    ) {}

    public function index(Request $request)
    {
        $query = RiskControlMapping::with([
            'risk.project',
            'frameworkControl.framework',
            'control',
            'mappedBy',
        ])->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('mapping_status', $request->status);
        }
        // Filter by risk
        if ($request->filled('risk_register_id')) {
            $query->where('risk_register_id', $request->risk_register_id);
        }

        $mappings = $query->paginate(50);

        $statuses = ['suggested', 'confirmed', 'rejected'];
        $risks = RiskRegister::select('id', 'serial_no', 'asset_process_service')
            ->orderBy('serial_no')
            ->get();

        return view('admin.control-mappings.index', compact('mappings', 'statuses', 'risks'));
    }

    public function confirm(Request $request, RiskControlMapping $mapping)
    {
        $this->mappingService->confirmMapping($mapping->id);
        return back()->with('success', 'Mapping confirmed.');
    }

    public function reject(Request $request, RiskControlMapping $mapping)
    {
        $this->mappingService->rejectMapping($mapping->id);
        return back()->with('success', 'Mapping rejected.');
    }

    public function export()
    {
        return Excel::download(
            new ControlMappingSheetExport(),
            'control-mapping-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
