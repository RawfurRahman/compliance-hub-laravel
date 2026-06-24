<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\RiskManagement\Services\WorkbookImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RiskImportController extends Controller
{
    private WorkbookImportService $service;

    public function __construct()
    {
        $this->service = new WorkbookImportService();
    }

    /**
     * Show import upload form.
     */
    public function showImportForm(Project $project)
    {
        $this->authorize('view', $project);
        return view('risk-management.import', compact('project'));
    }

    /**
     * Parse file, prefill mappings and run dry-run validation.
     */
    public function dryRun(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240', // Limit file size to 10MB
        ]);

        $file = $request->file('file');
        
        // Save to temp imports folder
        $tempPath = $file->storeAs('imports/temp', Str::random(20) . '.' . $file->getClientOriginalExtension());
        $absolutePath = Storage::path($tempPath);

        try {
            $suggestedMappings = $this->service->getHeaderMappings($absolutePath);
            $dryRunRows = $this->service->dryRun($absolutePath, $suggestedMappings);

            return response()->json([
                'success' => true,
                'temp_file' => $tempPath,
                'suggested_mappings' => $suggestedMappings,
                'validation_rows' => $dryRunRows,
            ]);
        } catch (\Exception $e) {
            Storage::delete($tempPath);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Confirm mapping and run import.
     */
    public function confirmImport(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $request->validate([
            'temp_file' => 'required|string',
            'mappings' => 'required|array',
        ]);

        $tempFile = $request->input('temp_file');
        $absolutePath = Storage::path($tempFile);

        if (!file_exists($absolutePath)) {
            return response()->json([
                'success' => false,
                'error' => 'Temporary file not found or expired. Please upload again.',
            ], 400);
        }

        try {
            $mappings = $request->input('mappings');
            
            $result = $this->service->import($absolutePath, $mappings, $project->id);
            
            // Clean up file
            Storage::delete($tempFile);

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$result['imported_count']} risk registers.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
