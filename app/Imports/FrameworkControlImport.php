<?php

namespace App\Imports;

use App\Models\FrameworkControl;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class FrameworkControlImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    protected $frameworkId;
    protected $lastPrefix = null;
    protected $lastNum = null;

    public function __construct($frameworkId)
    {
        $this->frameworkId = $frameworkId;
    }

    public function model(array $row)
    {
        $controlIdRaw = null;
        $domain = 'General';
        $requirementDescription = null;
        $requiredEvidence = null;

        // Search keys for partial keyword matches to support complex headers (e.g. "Control ID / Control No")
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(str_replace([' ', '_', '-', '/'], '', (string)$key));

            // 1. Match Control ID
            if ($controlIdRaw === null && (
                str_contains($normalizedKey, 'controlid') || 
                str_contains($normalizedKey, 'controlno') || 
                str_contains($normalizedKey, 'controlnum') || 
                str_contains($normalizedKey, 'controlref') || 
                str_contains($normalizedKey, 'clause')
            )) {
                $controlIdRaw = $value;
                continue; // Avoid matching this key for other fields
            }

            // 2. Match Domain
            if (str_contains($normalizedKey, 'domain')) {
                $domain = $value;
                continue;
            }

            // 3. Match Description
            if ($requirementDescription === null && (
                str_contains($normalizedKey, 'requirement') || 
                str_contains($normalizedKey, 'description') || 
                str_contains($normalizedKey, 'desc')
            )) {
                $requirementDescription = $value;
                continue;
            }

            // 4. Match Evidence
            if ($requiredEvidence === null && (
                str_contains($normalizedKey, 'evidence') || 
                str_contains($normalizedKey, 'proof')
            )) {
                $requiredEvidence = $value;
                continue;
            }
        }

        if (!$controlIdRaw || !$requirementDescription) {
            return null;
        }

        $controlId = trim((string)$controlIdRaw);

        // Sequential autocorrect for Excel float truncation (e.g. 5.10 parsed as 5.1, 5.20 parsed as 5.2)
        if (preg_match('/^(.*?)(\d+)$/', $controlId, $matches)) {
            $prefix = $matches[1];
            $num = (int)$matches[2];

            if ($this->lastPrefix !== null && $prefix === $this->lastPrefix) {
                // If previous number was 9 and current is 1 (after a decimal point, e.g., 5.9 -> 5.10),
                // it was read as 5.1 by PhpSpreadsheet/Excel. Convert it back to 5.10.
                if ($this->lastNum === 9 && $num === 1) {
                    $num = 10;
                    $controlId = $prefix . '10';
                } elseif ($this->lastNum === 19 && $num === 2) {
                    $num = 20;
                    $controlId = $prefix . '20';
                } elseif ($this->lastNum === 29 && $num === 3) {
                    $num = 30;
                    $controlId = $prefix . '30';
                } elseif ($this->lastNum === 39 && $num === 4) {
                    $num = 40;
                    $controlId = $prefix . '40';
                }
            }

            $this->lastPrefix = $prefix;
            $this->lastNum = $num;
        }

        return new FrameworkControl([
            'framework_id'            => $this->frameworkId,
            'control_id'              => $controlId,
            'domain'                  => trim((string)$domain),
            'requirement_description' => trim((string)$requirementDescription),
            'required_evidence'       => $requiredEvidence ? trim((string)$requiredEvidence) : null,
        ]);
    }
}
