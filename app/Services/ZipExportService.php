<?php

namespace App\Services;

use App\Models\Project;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ZipExportService
{
    /**
     * Create a ZIP package of all accepted evidence files for a project.
     *
     * @param Project $project
     * @return array
     * @throws \Exception
     */
    public function createEvidencePackage(Project $project): array
    {
        $zip = new ZipArchive();
        $zipName = 'evidence_export_' . $project->id . '_' . time() . '.zip';
        
        $tempDir = storage_path('app/public/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $zipPath = $tempDir . '/' . $zipName;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Cannot create zip archive: {$zipPath}");
        }

        $isPci = $project->module_type === 'pci_dss';
        
        // Fetch only accepted evidence files
        $evidenceFiles = $project->evidenceFiles()
            ->where('hitl_status', 'accepted')
            ->get();

        foreach ($evidenceFiles as $file) {
            if (!Storage::disk('public')->exists($file->file_path)) {
                continue;
            }

            $absolutePath = Storage::disk('public')->path($file->file_path);
            
            // Format folder name based on project type
            if ($isPci) {
                $reqNum = $file->requirement ? $file->requirement->req_num : 'unknown';
                $folderName = 'Requirement_' . Str::slug($reqNum);
            } else {
                $controlId = $file->frameworkControl ? $file->frameworkControl->control_id : 'unknown';
                $folderName = 'Control_' . Str::slug($controlId);
            }
            
            $zip->addFile($absolutePath, $folderName . '/' . $file->original_filename);
        }

        $zip->close();

        return [
            'path' => $zipPath,
            'name' => $zipName,
        ];
    }
}
