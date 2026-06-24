<?php

namespace App\Modules\RiskManagement\Exports;

use App\Models\Framework;
use App\Models\FrameworkControl;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ControlMappingSheetExport implements FromCollection, WithHeadings, WithTitle
{
    public function collection(): Collection
    {
        $frameworks = Framework::whereIn('slug', ['pci_dss', 'iso_27001', 'bb_ict', 'swift_cscf'])
            ->where('is_active', true)
            ->pluck('id', 'slug');

        // Fetch all controls grouped by framework
        $allControls = FrameworkControl::with('framework')->get()->groupBy('framework_id');

        // Build rows aligned by cross-reference — group by control_id pattern
        // We'll export ALL controls, each row representing a cross-framework mapping
        $rows = collect();

        // Use PCI DSS as the primary axis if it exists
        $primaryIds = $allControls->get($frameworks['pci_dss'] ?? 0, collect());

        if ($primaryIds->isNotEmpty()) {
            foreach ($primaryIds as $pciControl) {
                $rows->push($this->buildRow(
                    $pciControl,
                    $allControls->get($frameworks['pci_dss'] ?? 0, collect()),
                    $allControls->get($frameworks['iso_27001'] ?? 0, collect()),
                    $allControls->get($frameworks['bb_ict'] ?? 0, collect()),
                    $allControls->get($frameworks['swift_cscf'] ?? 0, collect()),
                    'pci_dss'
                ));
            }
        } else {
            // Fall back to any available framework as primary axis
            $primaryAxis = null;
            $primarySlug = null;
            foreach (['iso_27001', 'bb_ict', 'swift_cscf'] as $slug) {
                if (isset($frameworks[$slug]) && $allControls->has($frameworks[$slug])) {
                    $primaryAxis = $allControls->get($frameworks[$slug]);
                    $primarySlug = $slug;
                    break;
                }
            }

            if ($primaryAxis) {
                foreach ($primaryAxis as $control) {
                    $rows->push($this->buildRow(
                        $control,
                        $allControls->get($frameworks['pci_dss'] ?? 0, collect()),
                        $allControls->get($frameworks['iso_27001'] ?? 0, collect()),
                        $allControls->get($frameworks['bb_ict'] ?? 0, collect()),
                        $allControls->get($frameworks['swift_cscf'] ?? 0, collect()),
                        $primarySlug
                    ));
                }
            }

            // Also add any PCI DSS controls that weren't cross-referenced
            if (isset($frameworks['pci_dss']) && $allControls->has($frameworks['pci_dss'])) {
                $existingPciRefs = $rows->pluck('pci_dss_ref')->filter()->values()->toArray();
                foreach ($allControls->get($frameworks['pci_dss']) as $pciControl) {
                    if (!in_array($pciControl->control_id, $existingPciRefs)) {
                        $rows->push($this->buildRow(
                            $pciControl,
                            $allControls->get($frameworks['pci_dss'], collect()),
                            $allControls->get($frameworks['iso_27001'] ?? 0, collect()),
                            $allControls->get($frameworks['bb_ict'] ?? 0, collect()),
                            $allControls->get($frameworks['swift_cscf'] ?? 0, collect()),
                            'pci_dss'
                        ));
                    }
                }
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'PCI DSS Ref',
            'PCI DSS Description',
            'ISO Ref',
            'ISO Description',
            'BB ICT Ref',
            'BB ICT Description',
            'SWIFT Ref',
            'SWIFT Description',
            'Status',
        ];
    }

    public function title(): string
    {
        return 'Control Mapping';
    }

    private function buildRow(
        $control,
        Collection $pciControls,
        Collection $isoControls,
        Collection $bbIctControls,
        Collection $swiftControls,
        string $primarySlug
    ): array {
        $pciDesc   = $primarySlug === 'pci_dss'   ? $control->requirement_description : $this->findDescByRef($control->pci_dss_ref, $pciControls);
        $isoDesc   = $primarySlug === 'iso_27001' ? $control->requirement_description : $this->findDescByRef($control->iso_ref, $isoControls);
        $bbIctDesc = $primarySlug === 'bb_ict'     ? $control->requirement_description : $this->findDescByRef($control->bb_ict_ref, $bbIctControls);
        $swiftDesc  = $primarySlug === 'swift_cscf' ? $control->requirement_description : $this->findDescByRef($control->swift_ref, $swiftControls);

        return [
            'pci_dss_ref'   => $primarySlug === 'pci_dss'   ? $control->control_id : ($control->pci_dss_ref ?? ''),
            'pci_desc'      => $pciDesc ?? ($primarySlug === 'pci_dss' ? '' : ''),
            'iso_ref'       => $primarySlug === 'iso_27001' ? $control->control_id : ($control->iso_ref ?? ''),
            'iso_desc'      => $isoDesc ?? ($primarySlug === 'iso_27001' ? '' : ''),
            'bb_ict_ref'    => $primarySlug === 'bb_ict'     ? $control->control_id : ($control->bb_ict_ref ?? ''),
            'bb_ict_desc'   => $bbIctDesc ?? ($primarySlug === 'bb_ict' ? '' : ''),
            'swift_ref'     => $primarySlug === 'swift_cscf' ? $control->control_id : ($control->swift_ref ?? ''),
            'swift_desc'    => $swiftDesc ?? ($primarySlug === 'swift_cscf' ? '' : ''),
            'status'        => $control->status ?? 'active',
        ];
    }

    private function findDescByRef(?string $ref, Collection $controls): ?string
    {
        if (empty($ref)) {
            return null;
        }

        $control = $controls->first(fn ($c) => $c->control_id === $ref);
        return $control?->requirement_description;
    }
}
