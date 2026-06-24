<?php

namespace App\Modules\RiskManagement\Console\Commands;

use App\Modules\RiskManagement\Exports\ControlMappingSheetExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ExportControlMappings extends Command
{
    protected $signature = 'rmm:export-control-mappings
                            {file? : Output file path (default: control-mapping-export.xlsx)}';

    protected $description = 'Export the Control Mapping catalog to Excel matching the workbook format';

    public function handle(): int
    {
        $file = $this->argument('file') ?? 'control-mapping-export.xlsx';

        $this->info("Exporting Control Mapping sheet to: {$file}");

        try {
            Excel::store(new ControlMappingSheetExport(), $file, 'local');
        } catch (\Exception $e) {
            $this->error("Export failed: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $fullPath = storage_path('app/' . $file);
        $this->info("Export complete. File saved to: {$fullPath}");

        return Command::SUCCESS;
    }
}
