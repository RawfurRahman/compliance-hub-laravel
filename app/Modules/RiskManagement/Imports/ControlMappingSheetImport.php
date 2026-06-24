<?php

namespace App\Modules\RiskManagement\Imports;

use App\Models\Framework;
use App\Models\FrameworkControl;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ControlMappingSheetImport implements WithMultipleSheets
{
    private ?int $frameworkId = null;
    private string $frameworkSlug;

    public function __construct(string $frameworkSlug)
    {
        $this->frameworkSlug = $frameworkSlug;
        $framework = Framework::where('slug', $frameworkSlug)->first();
        if ($framework) {
            $this->frameworkId = $framework->id;
        }
    }

    public function sheets(): array
    {
        return [
            'Control Mapping' => new ControlMappingSheetImportHandler($this->frameworkId, $this->frameworkSlug),
        ];
    }

    public function getFrameworkId(): ?int
    {
        return $this->frameworkId;
    }
}

class ControlMappingSheetImportHandler implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    private ?int $frameworkId;
    private string $frameworkSlug;

    public function __construct(?int $frameworkId, string $frameworkSlug)
    {
        $this->frameworkId = $frameworkId;
        $this->frameworkSlug = $frameworkSlug;
    }

    public function model(array $row)
    {
        if (!$this->frameworkId) {
            return null;
        }

        $controlId = $this->extractControlId($row);
        $domain    = $this->extractDomain($row);
        $description = $this->extractDescription($row);
        $evidence    = $this->extractEvidence($row);
        $status      = $this->extractStatus($row);

        if (empty($controlId) || empty($description)) {
            return null;
        }

        // Extract framework-specific references
        $pciDssRef  = $this->extractRef($row, ['pci dss ref', 'pci_dss_ref', 'pci', 'pci dss']);
        $isoRef     = $this->extractRef($row, ['iso ref', 'iso_ref', 'iso']);
        $bbIctRef   = $this->extractRef($row, ['bb ict ref', 'bb_ict_ref', 'bbict', 'bb ict']);
        $swiftRef   = $this->extractRef($row, ['swift ref', 'swift_ref', 'swift']);

        return FrameworkControl::updateOrCreate(
            [
                'framework_id' => $this->frameworkId,
                'control_id'   => $controlId,
            ],
            [
                'domain'                => $domain,
                'requirement_description' => $description,
                'required_evidence'     => $evidence,
                'status'                => $status,
                'pci_dss_ref'           => $pciDssRef,
                'iso_ref'               => $isoRef,
                'bb_ict_ref'            => $bbIctRef,
                'swift_ref'             => $swiftRef,
            ]
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Column extraction helpers                                          */
    /* ------------------------------------------------------------------ */

    private function extractControlId(array $row): ?string
    {
        $keys = ['control_id', 'controlid', 'control_no', 'controlno', 'control_num',
                 'controlnum', 'control_ref', 'controlref', 'clause', 'ref', 'id',
                 'requirement_no', 'requirementno', 'req_no', 'reqno'];

        return $this->firstValue($row, $keys);
    }

    private function extractDomain(array $row): string
    {
        $keys = ['domain', 'category', 'area', 'section', 'chapter', 'topic', 'family'];
        return $this->firstValue($row, $keys) ?? 'Uncategorized';
    }

    private function extractDescription(array $row): ?string
    {
        $keys = ['requirement_description', 'description', 'requirement', 'req_description',
                 'reqdesc', 'control_description', 'controldesc', 'desc', 'statement',
                 'requirement_statement', 'req_statement'];

        return $this->firstValue($row, $keys);
    }

    private function extractEvidence(array $row): ?string
    {
        $keys = ['required_evidence', 'evidence', 'required_evidence_proof',
                 'evidence_required', 'proof', 'artifact', 'evidence_proof'];

        return $this->firstValue($row, $keys);
    }

    private function extractStatus(array $row): ?string
    {
        $keys = ['status', 'control_status', 'mapping_status', 'state'];
        return $this->firstValue($row, $keys) ?? 'active';
    }

    private function extractRef(array $row, array $candidates): ?string
    {
        foreach ($candidates as $key) {
            $found = $this->firstValue($row, [$key]);
            if ($found !== null) {
                return $found;
            }
        }
        return null;

        // Try fuzzy header matching as fallback
        foreach ($row as $header => $value) {
            $normalized = strtolower(preg_replace('/[^a-z0-9]/', '', $header));
            foreach ($candidates as $candidate) {
                $normalizedCandidate = strtolower(preg_replace('/[^a-z0-9]/', '', $candidate));
                if ($normalized === $normalizedCandidate || similar_text($normalized, $normalizedCandidate) > 70) {
                    return $value ?? null;
                }
            }
        }

        return null;
    }

    private function firstValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') {
                return trim((string) $row[$key]);
            }
        }
        return null;
    }
}
