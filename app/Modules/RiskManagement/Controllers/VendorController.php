<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Models\Project;
use App\Modules\RiskManagement\Models\ThirdPartyVendor;
use App\Modules\RiskManagement\Services\ThirdPartyVendorService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function __construct(
        private ThirdPartyVendorService $service,
    ) {}

    public function index(Request $request, ?Project $project = null)
    {
        $vendors = $this->service->listForProject($project?->id);
        return response()->json(['data' => $vendors]);
    }

    public function store(Request $request, ?Project $project = null)
    {
        $data = $request->validate([
            'vendor_name' => 'required|string|max:255',
            'vendor_code' => 'required|string|max:100|unique:third_party_vendors,vendor_code',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'service_category' => 'nullable|string|max:100',
            'criticality' => 'sometimes|in:low,medium,high,critical',
            'risk_tier' => 'nullable|string|max:30',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date',
            'data_classification' => 'nullable|string|max:50',
            'data_shared' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,under_review,terminated',
            'notes' => 'nullable|string',
        ]);

        if ($project) {
            $data['project_id'] = $project->id;
        }

        $vendor = $this->service->create($data);

        return response()->json(['data' => $vendor], 201);
    }

    public function show(ThirdPartyVendor $vendor)
    {
        $vendor->load('assessments');
        return response()->json(['data' => $vendor]);
    }

    public function update(Request $request, ThirdPartyVendor $vendor)
    {
        $data = $request->validate([
            'vendor_name' => 'sometimes|string|max:255',
            'vendor_code' => 'sometimes|string|max:100|unique:third_party_vendors,vendor_code,' . $vendor->id,
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'service_category' => 'nullable|string|max:100',
            'criticality' => 'sometimes|in:low,medium,high,critical',
            'risk_tier' => 'nullable|string|max:30',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date',
            'data_classification' => 'nullable|string|max:50',
            'data_shared' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,under_review,terminated',
            'notes' => 'nullable|string',
        ]);

        $vendor = $this->service->update($vendor, $data);

        return response()->json(['data' => $vendor]);
    }

    public function destroy(ThirdPartyVendor $vendor)
    {
        $this->service->delete($vendor);
        return response()->json(['success' => true]);
    }
}
