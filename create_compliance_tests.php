<?php

use App\Models\User;
use App\Modules\Compliance\Models\ComplianceTest;
use App\Modules\Compliance\Models\ComplianceTestFrameworkLink;
use App\Modules\Compliance\Models\ComplianceTestFailure;
use App\Modules\Compliance\Models\Framework;

/**
 * Seed compliance test data for Stage 3 verification
 */n
function createComplianceTestData()
{
    // Get existing users and frameworks
    $users = User::all();
    $frameworks = Framework::all();

    if ($users->count() == 0 || $frameworks->count() == 0) {
        echo "No users or frameworks found. Create some first.\n";
        return;
    }

    // Create 4 diverse tests matching the specification
    $tests = [
        [
            'name' => 'Public SSH Access Control',
            'description' => 'Ensures all public-facing services require SSH key authentication',
            'owner_user_id' => $users->first()->id,
            'team' => 'Security Team',
            'test_type' => 'Automated',
            'sla_days' => 3,
            'status' => 'Overdue',
            'last_run_at' => '2026-06-24 10:00:00',
            'next_due_at' => '2026-06-25 10:00:00',
            'framework_ids' => [1, 2], // ISO 27001, PCI DSS
            'failing_entities' => [],
        ],
        [
            'name' => 'Database Encryption',
            'description' => 'Verifies all databases use AES-256 encryption at rest',
            'owner_user_id' => $users->skip(1)->first()->id,
            'team' => 'Infrastructure Team',
            'test_type' => 'Automated',
            'sla_days' => 7,
            'status' => 'Overdue',
            'last_run_at' => '2026-06-21 10:00:00',
            'next_due_at' => '2026-06-25 10:00:00',
            'framework_ids' => [1, 2, 3], // ISO 27001, PCI DSS, SWIFT
            'failing_entities' => [
                ['description' => 'AWS RDS Instance db-prod-01', 'detected_at' => '2026-06-24 10:00:00'],
                ['description' => 'PostgreSQL Server pg-prod', 'detected_at' => '2026-06-23 10:00:00'],
            ],
        ],
        [
            'name' => 'Cloud Storage Access Control',
            'description' => 'Validates S3 bucket access controls and IAM policies',
            'owner_user_id' => $users->skip(2)->first()->id,
            'team' => 'Platform Team',
            'test_type' => 'Automated',
            'sla_days' => 5,
            'status' => 'Due Soon',
            'last_run_at' => '2026-06-24 10:00:00',
            'next_due_at' => '2026-06-28 10:00:00',
            'framework_ids' => [1], // ISO 27001
            'failing_entities' => [
                ['description' => 'S3 Bucket data-shared', 'detected_at' => '2026-06-23 10:00:00'],
            ],
        ],
        [
            'name' => 'Application Security Testing',
            'description' => 'Manual penetration testing of web applications',
            'owner_user_id' => $users->first()->id,
            'team' => 'Security Team',
            'test_type' => 'Manual',
            'sla_days' => 30,
            'status' => 'Not Yet Run',
            'last_run_at' => null,
            'next_due_at' => null,
            'framework_ids' => [2, 3], // PCI DSS, SWIFT
            'failing_entities' => [],
        ],
    ];

    foreach ($tests as $testData) {
        $test = ComplianceTest::create([
            'name' => $testData['name'],
            'description' => $testData['description'],
            'owner_user_id' => $testData['owner_user_id'],
            'team' => $testData['team'],
            'test_type' => $testData['test_type'],
            'sla_days' => $testData['sla_days'],
            'status' => $testData['status'],
            'last_run_at' => $testData['last_run_at'],
            'next_due_at' => $testData['next_due_at'],
            'control_monitor_id' => null,
        ]);

        // Link to frameworks
        foreach ($testData['framework_ids'] as $frameworkId) {
            $test->frameworkLinks()->create([
                'framework_id' => $frameworkId,
                'resources_in_scope_count' => rand(10, 50),
            ]);
        }

        // Create failing entities
        if (!empty($testData['failing_entities'])) {
            foreach ($testData['failing_entities'] as $failureData) {
                $test->failures()->create([
                    'failing_entity_description' => $failureData['description'],
                    'detected_at' => $failureData['detected_at'],
                    'resolved_at' => null,
                ]);
            }
        }

        echo "✅ Created test: " . $test->name . " (ID: " . $test->id . ")\n";
    }

    // Verify data
    $totalTests = ComplianceTest::count();
    $passingTests = ComplianceTest::where('status', 'Passing')->count();
    $overdueTests = ComplianceTest::where('status', 'Overdue')->count();
    $dueSoonTests = ComplianceTest::where('status', 'Due Soon')->count();
    $notYetRunTests = ComplianceTest::where('status', 'Not Yet Run')->count();
    $needsRemediationTests = ComplianceTest::where('status', 'Needs Remediation')->count();

    $passingPercentage = $totalTests > 0 ? round(($passingTests / $totalTests) * 100, 1) : 0;

    echo "\n=== VERIFICATION ===\n";
    echo "Total Tests: $totalTests\n";
    echo "Passing Tests: $passingTests\n";
    echo "Overdue Tests: $overdueTests\n";
    echo "Due Soon Tests: $dueSoonTests\n";
    echo "Needs Remediation Tests: $needsRemediationTests\n";
    echo "Not Yet Run Tests: $notYetRunTests\n";
    echo "Passing Percentage: $passingPercentage%\n";

    echo "\n=== FRAMEWORK LINKAGES ===\n";
    ComplianceTest::each(function ($test) {
        echo "$test->name: " . $test->frameworkLinks->count() . " frameworks\n";
    });

    echo "\n=== FAILING ENTITIES ===\n";
    ComplianceTest::each(function ($test) {
        $activeFailures = $test->activeFailures()->count();
        if ($activeFailures > 0) {
            echo "$test->name: $activeFailures unresolved failures\n";
        }
    });
}

call_user_function();