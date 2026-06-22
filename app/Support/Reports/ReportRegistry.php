<?php

namespace App\Support\Reports;

use App\Models\Project;
use App\Models\Framework;

class ReportRegistry
{
    /**
     * Get all report definitions.
     */
    public static function all(): array
    {
        return [
            'pci_dss_roc' => [
                'type' => 'pci_dss_roc',
                'label' => 'Report on Compliance (ROC)',
                'description' => 'Official PCI DSS Assessment Report - Version 4.0.1',
                'version' => '4.0.1',
                'icon' => 'fa-file-pdf',
                'color' => 'sky',
                'exports' => ['pdf', 'html'],
                'frameworks' => ['pci_dss'],
            ],
            'pci_dss_aoc' => [
                'type' => 'pci_dss_aoc',
                'label' => 'Attestation of Compliance (AOC)',
                'description' => 'Signed attestation document for validation authorities',
                'version' => '4.0.1',
                'icon' => 'fa-certificate',
                'color' => 'emerald',
                'exports' => ['pdf'],
                'frameworks' => ['pci_dss'],
            ],
            'pci_dss_gap' => [
                'type' => 'pci_dss_gap',
                'label' => 'Gap Assessment Report',
                'description' => 'Analysis of non-compliant requirements and remediation steps',
                'version' => '4.0.1',
                'icon' => 'fa-chart-bar',
                'color' => 'amber',
                'exports' => ['pdf', 'html'],
                'frameworks' => ['pci_dss'],
            ],
        ];
    }

    /**
     * Get available reports for a specific project.
     */
    public static function getAvailableReports(Project $project): array
    {
        $reports = [];

        if ($project->module_type === 'pci_dss') {
            $reports = array_values(self::all());
        } else {
            // Retrieve dynamic framework
            $framework = Framework::where('slug', $project->module_type)
                ->where('is_active', true)
                ->first();

            if ($framework) {
                // Gap Report details
                $reports[] = [
                    'type' => 'unified_gap',
                    'label' => $framework->name . ' Gap Assessment Report',
                    'description' => 'Comprehensive Gap Analysis and control findings',
                    'version' => $framework->version ?? '1.0',
                    'icon' => 'fa-chart-bar',
                    'color' => 'sky',
                    'exports' => ['pdf', 'html'],
                    'frameworks' => [$project->module_type],
                    'disabled' => !$project->assessments()->where('framework_id', $framework->id)->where('type', 'Gap')->exists(),
                ];

                // Final Report details
                $reports[] = [
                    'type' => 'unified_final',
                    'label' => $framework->name . ' Final Assessment Report',
                    'description' => 'Official certification audit report and final compliance posture',
                    'version' => $framework->version ?? '1.0',
                    'icon' => 'fa-clipboard-check',
                    'color' => 'emerald',
                    'exports' => ['pdf', 'html'],
                    'frameworks' => [$project->module_type],
                    'disabled' => !$project->assessments()->where('framework_id', $framework->id)->where('type', 'Final')->exists(),
                ];
            }
        }

        return $reports;
    }
}
