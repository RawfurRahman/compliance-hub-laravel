<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Modules\RiskManagement\Models\ThirdPartyVendor;
use App\Modules\RiskManagement\Models\VendorAssessment;
use App\Modules\RiskManagement\Services\VendorAssessmentService;
use App\Modules\RiskManagement\Events\VendorAssessmentCompleted;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VendorAssessmentController extends Controller
{
    public function __construct(
        private VendorAssessmentService $service,
    ) {}

    public function index(ThirdPartyVendor $vendor)
    {
        return response()->json([
            'data' => $this->service->getForVendor($vendor->id),
        ]);
    }

    public function store(Request $request, ThirdPartyVendor $vendor)
    {
        $data = $request->validate([
            'assessment_type' => 'required|string|max:50',
            'assessment_date' => 'required|date',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|in:pending,in_progress,completed,failed',
            'notes' => 'nullable|string',
        ]);

        $data['vendor_id'] = $vendor->id;
        $assessment = $this->service->create($data);

        return response()->json(['data' => $assessment], 201);
    }

    public function show(ThirdPartyVendor $vendor, VendorAssessment $assessment)
    {
        $assessment->load('responses');
        return response()->json(['data' => $assessment]);
    }

    public function update(Request $request, ThirdPartyVendor $vendor, VendorAssessment $assessment)
    {
        $data = $request->validate([
            'assessment_type' => 'sometimes|string|max:50',
            'assessment_date' => 'sometimes|date',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|in:pending,in_progress,completed,failed',
            'findings_summary' => 'nullable|string',
            'remediation_required' => 'sometimes|boolean',
            'remediation_deadline' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $assessment = $this->service->update($assessment, $data);

        return response()->json(['data' => $assessment]);
    }

    public function destroy(ThirdPartyVendor $vendor, VendorAssessment $assessment)
    {
        $this->service->delete($assessment);
        return response()->json(['success' => true]);
    }

    public function complete(ThirdPartyVendor $vendor, VendorAssessment $assessment)
    {
        $assessment = $this->service->completeAssessment($assessment);

        VendorAssessmentCompleted::dispatch($assessment);

        return response()->json(['data' => $assessment]);
    }

    public function submitResponse(Request $request, ThirdPartyVendor $vendor, VendorAssessment $assessment)
    {
        $data = $request->validate([
            'responses' => 'required|array',
            'responses.*.question_key' => 'required|string|max:100',
            'responses.*.question_text' => 'required|string',
            'responses.*.section' => 'nullable|string|max:255',
            'responses.*.response_text' => 'nullable|string',
            'responses.*.response_type' => 'sometimes|in:text,yes_no,score_1_5,boolean,file',
            'responses.*.score' => 'nullable|numeric|min:0',
            'responses.*.max_score' => 'nullable|numeric|min:0',
            'responses.*.is_compliant' => 'nullable|boolean',
            'responses.*.comments' => 'nullable|string',
        ]);

        $created = [];
        foreach ($data['responses'] as $responseData) {
            $created[] = $this->service->submitResponse($assessment, $responseData);
        }

        $this->service->recalculateScore($assessment);

        return response()->json(['data' => $created], 201);
    }
}
