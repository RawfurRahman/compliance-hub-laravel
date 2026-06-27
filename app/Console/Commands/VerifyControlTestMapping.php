<?php

namespace App\Console\Commands;

use App\Models\Control;
use App\Models\User;
use App\Modules\Compliance\Models\ComplianceTest;
use Illuminate\Console\Command;

class VerifyControlTestMapping extends Command
{
    protected $signature = 'verify:control-test-mapping {control_code?}';
    protected $description = 'Link 3-4 tests to a Control and verify the X/Y Passing count';

    public function handle()
    {
        $code = $this->argument('control_code') ?? 'NET-4';

        $control = Control::firstOrCreate(
            ['code' => $code],
            [
                'control_code' => $code,
                'name' => $code . ' - Test Control',
                'title' => $code . ' - Test Control',
                'description' => 'Auto-created by verification script',
                'is_active' => true,
                'status' => 'active',
            ]
        );

        $this->info("Using Control: {$control->code} ({$control->title})");

        $user = User::first();
        if (!$user) {
            $this->error('No users found. Create a user first.');
            return 1;
        }

        $this->info("Using user: {$user->name} ({$user->email})");

        $testsData = [
            ['Firewall Rule Review', 'Passing', 'Automated'],
            ['Segmentation Check', 'Passing', 'Automated'],
            ['Access Log Audit', 'Overdue', 'Manual'],
            ['VPN Tunneling Test', 'Passing', 'Automated'],
        ];

        foreach ($testsData as [$name, $status, $type]) {
            ComplianceTest::updateOrCreate(
                ['name' => $name, 'control_id' => $control->id],
                [
                    'description' => null,
                    'owner_user_id' => $user->id,
                    'team' => 'Security',
                    'test_type' => $type,
                    'sla_days' => 30,
                    'status' => $status,
                    'last_run_at' => now(),
                    'next_due_at' => now()->addDays(30),
                    'control_monitor_id' => null,
                    'integration_id' => null,
                    'control_id' => $control->id,
                ]
            );
            $this->line("  Linked test: {$name} ({$status})");
        }

        $control->load('complianceTests');
        $tests = $control->complianceTests;
        $total = $tests->count();
        $passing = $tests->where('status', 'Passing')->count();

        $this->newLine();
        $this->info("=== Verification Result ===");
        $this->line("Control: {$control->code}");
        $this->line("Total mapped tests: {$total}");
        $this->line("Passing: {$passing}");
        $this->line("X/Y Passing: {$passing}/{$total}");
        $this->newLine();
        $this->table(
            ['Name', 'Status', 'Type'],
            $tests->map(fn($t) => [$t->name, $t->status, $t->test_type])
        );
        $this->newLine();
        $this->info("Visit: " . route('admin.controls.edit', $control));
        $this->info("Expected X/Y = {$passing}/{$total} at the top of 'Mapped Compliance Tests' section.");

        return 0;
    }
}
