<?php

namespace App\Modules\RiskManagement\Console\Commands;

use App\Modules\RiskManagement\Imports\ControlMappingSheetImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportControlMappings extends Command
{
    protected $signature = 'rmm:import-control-mappings
                            {file : Path to the Excel workbook (.xlsx or .csv)}
                            {framework : Framework slug (e.g. pci_dss, iso_27001, bb_ict, swift_cscf)}
                            {--sheet=Control Mapping : Sheet name to import from}';

    protected $description = 'Import the Control Mapping sheet from a workbook into framework_controls';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $frameworkSlug = $this->argument('framework');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Importing Control Mapping sheet from: {$filePath}");
        $this->info("Target framework: {$frameworkSlug}");

        $import = new ControlMappingSheetImport($frameworkSlug);
        $frameworkId = $import->getFrameworkId();

        if (!$frameworkId) {
            $this->error("Framework '{$frameworkSlug}' not found. Make sure it exists in the frameworks table.");
            return Command::FAILURE;
        }

        $this->info("Framework ID: {$frameworkId}");

        try {
            Excel::import($import, $filePath);
        } catch (\Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $count = \App\Models\FrameworkControl::where('framework_id', $frameworkId)->count();
        $this->info("Import complete. Framework now has {$count} control(s).");

        return Command::SUCCESS;
    }
}
