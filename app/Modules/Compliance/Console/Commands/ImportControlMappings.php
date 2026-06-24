<?php

namespace App\Modules\Compliance\Console\Commands;

use App\Modules\Compliance\Services\MappingImportService;
use Illuminate\Console\Command;

class ImportControlMappings extends Command
{
    protected $signature = 'compliance:import-mappings {file} {--framework=}';
    protected $description = 'Import framework-to-control mappings from a JSON or CSV file';

    public function handle(MappingImportService $service): int
    {
        $file = $this->argument('file');
        $frameworkId = $this->option('framework');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return Command::FAILURE;
        }

        $mappings = json_decode(file_get_contents($file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON file.');
            return Command::FAILURE;
        }

        if ($frameworkId) {
            $mappings = array_filter($mappings, fn ($m) => ($m['framework_id'] ?? null) == $frameworkId);
        }

        $count = $service->importMappings($mappings);
        $this->info("Imported {$count} mappings.");

        return Command::SUCCESS;
    }
}
