<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Framework;
use App\Models\FrameworkControl;

/**
 * FrameworkControlSeeder
 *
 * Reads a "Control Mapping" Excel workbook and seeds FrameworkControl rows
 * for the four frameworks below. The workbook is expected at:
 *
 *   storage/app/seeders/control_mapping.xlsx
 *
 * It parses the side-by-side multi-framework columns from the sheet.
 *
 * Run:
 *   php artisan db:seed --class=FrameworkControlSeeder
 */
class FrameworkControlSeeder extends Seeder
{
    /**
     * The four frameworks that must exist before controls are imported.
     */
    private array $frameworks = [
        'pci_dss_v4' => [
            'name'        => 'PCI DSS',
            'version'     => 'v 4.0',
            'description' => 'Payment Card Industry Data Security Standard v4.0',
            'is_active'   => false,
        ],
        'iso_27001_2022' => [
            'name'        => 'ISO 27001:2022',
            'version'     => '2022',
            'description' => 'Information Security Management Systems (2022 edition)',
            'is_active'   => false,
        ],
        'bb_ict' => [
            'name'        => 'BB ICT Guidelines',
            'version'     => 'current',
            'description' => 'Bangladesh Bank ICT Security Guidelines',
            'is_active'   => false,
        ],
        'swift_cscf_2026' => [
            'name'        => 'SWIFT CSCF',
            'version'     => '2026',
            'description' => 'SWIFT Customer Security Controls Framework 2026',
            'is_active'   => true,
        ],
    ];

    public function run(): void
    {
        // ----------------------------------------------------------------
        // 1. Ensure all four Framework rows exist
        // ----------------------------------------------------------------
        $frameworkModels = [];
        foreach ($this->frameworks as $slug => $attrs) {
            $frameworkModels[$slug] = Framework::firstOrCreate(
                ['slug' => $slug],
                $attrs
            );
            $this->command->info("Framework ready: {$attrs['name']} (slug={$slug})");
        }

        // ----------------------------------------------------------------
        // 2. Locate the Excel workbook
        // ----------------------------------------------------------------
        $relativePath = 'seeders/control_mapping.xlsx';
        $absolutePath = storage_path('app/' . $relativePath);

        if (! file_exists($absolutePath)) {
            $this->command->warn(
                "Control mapping file not found at: {$absolutePath}\n" .
                "Place the Excel file there and re-run the seeder.\n" .
                "Frameworks have been created; controls were NOT imported."
            );
            return;
        }

        // ----------------------------------------------------------------
        // 3. Import controls for each framework
        // ----------------------------------------------------------------
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($absolutePath);
            $sheet = $spreadsheet->getSheetByName('Control Mapping');
            if (!$sheet) {
                $this->command->error("Sheet 'Control Mapping' not found in {$absolutePath}");
                return;
            }
        } catch (\Exception $e) {
            $this->command->error('Could not read Excel file: ' . $e->getMessage());
            return;
        }

        $frameworksMap = [
            'pci_dss_v4' => ['ref_col' => 0, 'desc_col' => 1, 'domain' => 'PCI DSS'],
            'iso_27001_2022' => ['ref_col' => 3, 'desc_col' => 4, 'domain' => 'ISO 27001'],
            'bb_ict' => ['ref_col' => 6, 'desc_col' => 7, 'domain' => 'BB ICT Guidelines'],
            'swift_cscf_2026' => ['ref_col' => 9, 'desc_col' => 10, 'domain' => 'SWIFT CSCF'],
        ];

        $totalRows = $sheet->getHighestRow();
        $this->command->info("Parsing 'Control Mapping' sheet ({$totalRows} rows)...");

        $counts = [
            'pci_dss_v4' => 0,
            'iso_27001_2022' => 0,
            'bb_ict' => 0,
            'swift_cscf_2026' => 0,
        ];

        for ($r = 3; $r <= $totalRows; $r++) {
            foreach ($frameworksMap as $slug => $cols) {
                $refVal = $sheet->getCellByColumnAndRow($cols['ref_col'] + 1, $r)->getValue();
                $descVal = $sheet->getCellByColumnAndRow($cols['desc_col'] + 1, $r)->getValue();

                $refVal = trim((string)$refVal);
                $descVal = trim((string)$descVal);

                if (empty($refVal) || strtolower($refVal) === 'no relevant control match found' || strtolower($refVal) === 'n/a') {
                    continue;
                }

                // Support comma-separated references in the Ref columns
                $refs = array_map('trim', explode(',', $refVal));
                foreach ($refs as $singleRef) {
                    if (empty($singleRef)) {
                        continue;
                    }

                    FrameworkControl::updateOrCreate(
                        [
                            'framework_id' => $frameworkModels[$slug]->id,
                            'control_id'   => $singleRef,
                        ],
                        [
                            'domain'                  => $cols['domain'],
                            'requirement_description' => $descVal,
                            'required_evidence'       => null,
                        ]
                    );
                    $counts[$slug]++;
                }
            }
        }

        foreach ($counts as $slug => $count) {
            $this->command->info("  Imported/Updated {$count} controls for {$slug}.");
        }

        $this->command->info('FrameworkControlSeeder complete.');
    }
}
