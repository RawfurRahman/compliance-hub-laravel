<?php

namespace App\Modules\RiskManagement\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\WorkbookRiskSeeder;

class SeedWorkbookRisk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rmm:seed {--source= : The source of seed data (e.g. workbook)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the Risk Management Module database tables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $source = $this->option('source');

        if ($source === 'workbook') {
            $this->info('Running WorkbookRiskSeeder...');
            
            try {
                // Call seeder directly
                $seeder = new WorkbookRiskSeeder();
                $seeder->run();
                
                $this->info('Successfully seeded risk register from workbook.');
                return self::SUCCESS;
            } catch (\Exception $e) {
                $this->error('Error running seeder: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        $this->error('Invalid or missing source. Please use --source=workbook');
        return self::FAILURE;
    }
}
