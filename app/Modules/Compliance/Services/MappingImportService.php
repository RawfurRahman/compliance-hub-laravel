<?php

namespace App\Modules\Compliance\Services;

use App\Models\Control;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Modules\Compliance\Models\FrameworkControlMap;
use App\Modules\Compliance\Models\FrameworkVersion;
use Illuminate\Support\Collection;

class MappingImportService
{
    public function importMappings(array $mappings, ?int $createdBy = null): int
    {
        $imported = 0;

        foreach ($mappings as $mapping) {
            $control = Control::find($mapping['control_id'] ?? 0);
            $frameworkControl = FrameworkControl::find($mapping['framework_control_id'] ?? 0);

            if (!$control || !$frameworkControl) {
                continue;
            }

            $versionId = null;
            if (!empty($mapping['framework_version_id'])) {
                $versionId = $mapping['framework_version_id'];
            } elseif (!empty($mapping['version'])) {
                $framework = Framework::find($frameworkControl->framework_id);
                if ($framework) {
                    $version = $this->getOrCreateVersion($framework, $mapping['version'], $createdBy);
                    $versionId = $version->id;
                }
            }

            FrameworkControlMap::updateOrCreate(
                [
                    'control_id' => $control->id,
                    'framework_control_id' => $frameworkControl->id,
                ],
                [
                    'framework_version_id' => $versionId,
                    'mapping_type' => $mapping['mapping_type'] ?? 'direct',
                    'mapping_notes' => $mapping['mapping_notes'] ?? null,
                    'effectiveness_weight' => $mapping['effectiveness_weight'] ?? null,
                    'created_by' => $createdBy,
                ]
            );

            $imported++;
        }

        return $imported;
    }

    public function getOrCreateVersion(Framework $framework, string $version, ?int $createdBy = null): FrameworkVersion
    {
        return FrameworkVersion::firstOrCreate(
            [
                'framework_id' => $framework->id,
                'version' => $version,
            ],
            [
                'is_active' => true,
                'created_by' => $createdBy,
            ]
        );
    }

    public function previewMappings(array $mappings): Collection
    {
        $results = collect();

        foreach ($mappings as $mapping) {
            $control = Control::find($mapping['control_id'] ?? 0);
            $frameworkControl = FrameworkControl::with('framework')->find($mapping['framework_control_id'] ?? 0);

            $results->push([
                'control_code' => $control?->control_code ?? 'NOT FOUND',
                'control_name' => $control?->name ?? 'NOT FOUND',
                'framework_control_id' => $frameworkControl?->control_id ?? 'NOT FOUND',
                'framework' => $frameworkControl?->framework?->name ?? 'NOT FOUND',
                'mapping_type' => $mapping['mapping_type'] ?? 'direct',
                'exists' => $control && $frameworkControl ? FrameworkControlMap::where('control_id', $control->id)
                    ->where('framework_control_id', $frameworkControl->id)->exists() : false,
            ]);
        }

        return $results;
    }

    public function getMappingsForControl(int $controlId): Collection
    {
        return FrameworkControlMap::where('control_id', $controlId)
            ->with('frameworkControl.framework', 'frameworkVersion')
            ->get();
    }

    public function getMappingsForFrameworkControl(int $frameworkControlId): Collection
    {
        return FrameworkControlMap::where('framework_control_id', $frameworkControlId)
            ->with('control', 'frameworkVersion')
            ->get();
    }
}
