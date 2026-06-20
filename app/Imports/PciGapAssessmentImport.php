<?php

namespace App\Imports;

use App\Models\PciGapAssessment;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Carbon\Carbon;

class PciGapAssessmentImport implements ToModel, WithStartRow
{
    protected $projectId;

    public function __construct($projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * Start reading data from row 3, bypassing the complex multi-line headers.
     */
    public function startRow(): int
    {
        return 3;
    }

    public function model(array $row)
    {
        // Skip completely empty rows
        if (!isset($row[0])) {
            return null;
        }

        $requirementText = trim($row[0]);
        
        // Detect if this row is a major section header (e.g., "Requirement 1: ...")
        // Section headers in the document typically have no other columns filled out
        $isSectionHeader = false;
        if (str_starts_with($requirementText, 'Requirement') && empty($row[1]) && empty($row[6])) {
            $isSectionHeader = true;
        }

        // Parse milestone date securely
        $milestoneDate = null;
        if (!empty($row[5])) {
            try {
                // Handle Excel date serial numbers or standard date strings
                $milestoneDate = is_numeric($row[5]) 
                    ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[5]) 
                    : Carbon::parse($row[5]);
            } catch (\Exception $e) {
                $milestoneDate = null;
            }
        }

        // Normalize status
        $status = 'Pending';
        $rawStatus = strtolower(trim($row[1] ?? ''));
        if ($rawStatus === 'yes') $status = 'Yes';
        elseif ($rawStatus === 'n/a') $status = 'N/A';
        elseif ($rawStatus === 'no') $status = 'No';

        return new PciGapAssessment([
            'project_id'        => $this->projectId,
            'requirement_text'  => $requirementText,
            'is_section_header' => $isSectionHeader,
            'status'            => $isSectionHeader ? null : $status,
            'na_explanation'    => $row[2] ?? null,
            'milestone_date'    => $milestoneDate,
            'comments'          => $row[6] ?? null,
        ]);
    }
}
