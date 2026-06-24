<?php

namespace App\Modules\RiskManagement\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\RiskManagement\Services\WorkbookImportService;
use App\Models\Project;

class ImportWorkbookRisk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rmm:import {file : Path to the XLSX/CSV file} {--preset= : Predefined preset for headers mapping} {--project-id=1 : Project ID to link imported risks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import workbook risks into the database with mapping configurations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $preset = $this->option('preset');
        $projectId = intval($this->option('project-id'));

        if (!file_exists($filePath)) {
            $this->error("Target workbook file not found at: {$filePath}");
            return self::FAILURE;
        }

        $project = Project::find($projectId);
        if (!$project) {
            $this->error("Project with ID {$projectId} not found.");
            return self::FAILURE;
        }

        $this->info("Parsing spreadsheet: {$filePath}...");
        $service = new WorkbookImportService();

        try {
            $columnMappings = $service->getHeaderMappings($filePath);
            
            // If preset is workbook_predefined, check if matches are found. If not, mapping defaults are used.
            $this->info("Mappings pre-suggested based on headers:");
            foreach ($columnMappings as $map) {
                $this->line("  [Column {$map['col']}] '{$map['header']}' => " . ($map['db_field'] ?: 'SKIP'));
            }

            // Run import
            $this->info("Running import transaction...");
            $result = $service->import($filePath, $columnMappings, $projectId);

            if ($result['success']) {
                $this->info("Successfully imported {$result['imported_count']} risk registers into Project ID: {$projectId}.");
                return self::SUCCESS;
            }

            $this->error("Import process failed.");
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error("Error running import: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
