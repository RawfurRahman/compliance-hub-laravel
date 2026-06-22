<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Framework;
use App\Imports\FrameworkControlImport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * FrameworkControlSeeder
 *
 * Reads a "Control Mapping" Excel workbook and seeds FrameworkControl rows
 * for the four frameworks below.  The workbook is expected at:
 *
 *   storage/app/seeders/control_mapping.xlsx
 *
 * Expected columns (fuzzy-matched by FrameworkControlImport):
 *   - Control ID / Control No / Control Ref / Clause
 *   - Control Description / Requirement / Description / Desc
 *   - Domain  (optional)
 *   - Required Evidence / Evidence / Proof  (optional)
 *
 * The PCI DSS Status, ISO 27001 Status, BB ICT Status, and SWIFT CSCF
 * Status columns in the source sheet are informational only; they are not
 * stored in framework_controls (no matching column exists in the schema).
 * If you need them later, add a nullable JSON column and extend the import.
 *
 * Run:
 *   php artisan db:seed --class=FrameworkControlSeeder
 *
 * Or place it in DatabaseSeeder::$seeders so it runs with db:seed.
 */
class FrameworkControlSeeder extends Seeder
{
    /**
     * The four frameworks that must exist before controls are imported.
     * Each entry maps a slug to the firstOrCreate attributes.
     */
    private array $frameworks = [
        'pci_dss_v4' => [
            'name'        => 'PCI DSS v4.0',
            'version'     => 'v4.0',
            'description' => 'Payment Card Industry Data Security Standard v4.0',
            'is_active'   => true,
        ],
        'iso_27001_2022' => [
            'name'        => 'ISO 27001:2022',
            'version'     => '2022',
            'description' => 'Information Security Management Systems (2022 edition)',
            'is_active'   => true,
        ],
        'bb_ict' => [
            'name'        => 'BB ICT Guidelines',
            'version'     => 'current',
            'description' => 'Bangladesh Bank ICT Security Guidelines',
            'is_active'   => true,
        ],
        'swift_cscf_2026' => [
            'name'        => 'SWIFT CSCF 2026',
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
        //
        // The workbook may be organised in one of two ways:
        //   (a) One sheet per framework — sheet names must contain the
        //       framework slug or a recognisable keyword (see mapping below).
        //   (b) A single sheet with all controls — imported into every
        //       framework (useful when the sheet already has status columns
        //       that distinguish frameworks).
        //
        // Adjust $sheetToSlug to match your actual sheet names.
        // ----------------------------------------------------------------
        $sheetToSlug = [
            // Sheet name keyword (lowercase)  =>  framework slug
            'pci'    => 'pci_dss_v4',
            'iso'    => 'iso_27001_2022',
            'bb'     => 'bb_ict',
            'swift'  => 'swift_cscf_2026',
        ];

        try {
            $reader    = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($absolutePath);
            $sheetNames = $reader->listWorksheetNames($absolutePath);
        } catch (\Exception $e) {
            $this->command->error('Could not read sheet names: ' . $e->getMessage());
            return;
        }

        if (count($sheetNames) === 1) {
            // Single-sheet workbook: import into all four frameworks
            $this->command->info('Single-sheet workbook detected — importing into all four frameworks.');
            foreach ($frameworkModels as $slug => $framework) {
                $this->importSheet($absolutePath, $sheetNames[0], $framework->id, $slug);
            }
        } else {
            // Multi-sheet workbook: match each sheet to a framework by keyword
            foreach ($sheetNames as $sheetName) {
                $lower = strtolower($sheetName);
                $matched = false;
                foreach ($sheetToSlug as $keyword => $slug) {
                    if (str_contains($lower, $keyword)) {
                        $framework = $frameworkModels[$slug];
                        $this->importSheet($absolutePath, $sheetName, $framework->id, $slug);
                        $matched = true;
                        break;
                    }
                }
                if (! $matched) {
                    $this->command->warn("Sheet '{$sheetName}' did not match any framework keyword — skipped.");
                }
            }
        }

        $this->command->info('FrameworkControlSeeder complete.');
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function importSheet(string $path, string $sheet, int $frameworkId, string $slug): void
    {
        $this->command->info("  Importing sheet '{$sheet}' -> framework slug '{$slug}' (id={$frameworkId})");
        try {
            Excel::import(
                new FrameworkControlImport($frameworkId),
                $path,
                null,
                \Maatwebsite\Excel\Excel::XLSX,
                ['sheet' => $sheet]
            );
            $this->command->info("  Done.");
        } catch (\Exception $e) {
            $this->command->error("  Failed: " . $e->getMessage());
        }
    }
}
