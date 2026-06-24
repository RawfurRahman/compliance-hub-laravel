<?php

namespace App\Modules\RiskManagement\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\RiskManagement\Services\MigrationService;

class MigrateLegacyRisks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rmm:migrate-legacy {--project-id=1 : Project ID to run legacy migration for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy PCI/ISO gap assessment records to unified risk register';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projectId = intval($this->option('project-id'));
        $this->info("Starting legacy risk migration for Project ID: {$projectId}...");

        $service = new MigrationService();
        
        try {
            $result = $service->migrateLegacyAssessments($projectId);
            
            if ($result['success']) {
                $this->info("Legacy migration completed successfully.");
                $this->line("  Migrated ISO Gap Assessments: " . $result['iso_migrated']);
                $this->line("  Migrated PCI Gap Assessments: " . $result['pci_migrated']);
                return self::SUCCESS;
            }
            
            $this->error("Legacy migration failed.");
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error("Error running legacy migration: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
