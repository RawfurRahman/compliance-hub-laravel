<?php

namespace App\Modules\Compliance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\Compliance\Services\MappingImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MappingImportController extends Controller
{
    public function __construct(
        private MappingImportService $service,
    ) {}

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mappings' => 'required|array',
            'mappings.*.control_id' => 'required|exists:controls,id',
            'mappings.*.framework_control_id' => 'required|exists:framework_controls,id',
            'mappings.*.mapping_type' => 'nullable|string|in:direct,related,derived',
            'mappings.*.mapping_notes' => 'nullable|string',
            'mappings.*.effectiveness_weight' => 'nullable|numeric|min:0|max:100',
            'mappings.*.version' => 'nullable|string',
        ]);

        $count = $this->service->importMappings($data['mappings'], Auth::id());

        return response()->json(['imported' => $count]);
    }

    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mappings' => 'required|array',
            'mappings.*.control_id' => 'required|exists:controls,id',
            'mappings.*.framework_control_id' => 'required|exists:framework_controls,id',
            'mappings.*.mapping_type' => 'nullable|string',
        ]);

        $results = $this->service->previewMappings($data['mappings']);

        return response()->json($results);
    }
}
