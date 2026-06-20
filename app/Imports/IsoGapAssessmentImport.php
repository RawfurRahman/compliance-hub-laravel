<?php

namespace App\Imports;

use App\Models\IsoGapAssessment;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class IsoGapAssessmentImport implements ToModel, WithHeadingRow
{
    protected int $projectId;

    public function __construct(int $projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * Map each Excel row to an IsoGapAssessment model.
     *
     * Expected heading row columns (case-insensitive, spaces converted to underscores
     * by Maatwebsite/Excel's WithHeadingRow):
     *   serial_no, status, observation_title, risk_rating,
     *   current_state_observation, gap_description, impact_risk,
     *   recommendation, relevant_standard_reference
     */
    public function model(array $row): IsoGapAssessment
    {
        $validRatings  = ['High', 'Medium', 'Low'];
        $validStatuses = ['Open', 'Closed', 'In Progress'];

        $riskRating = ucfirst(strtolower(trim($row['risk_rating'] ?? '')));
        $status     = trim($row['status'] ?? 'Open');

        return new IsoGapAssessment([
            'project_id'        => $this->projectId,
            'serial_no'         => trim($row['serial_no'] ?? ''),
            'clause_reference'  => trim($row['relevant_standard_reference'] ?? ''),
            'observation_title' => trim($row['observation_title'] ?? ''),
            'risk_rating'       => in_array($riskRating, $validRatings)  ? $riskRating : 'Low',
            'current_state'     => trim($row['current_state_observation'] ?? ''),
            'gap_description'   => trim($row['gap_description'] ?? ''),
            'impact_risk'       => trim($row['impact_risk'] ?? ''),
            'recommendation'    => trim($row['recommendation'] ?? ''),
            'status'            => in_array($status, $validStatuses) ? $status : 'Open',
        ]);
    }
}
