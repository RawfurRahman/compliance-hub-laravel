<?php

namespace Database\Seeders;

use App\Modules\Compliance\Models\ComplianceTestTemplate;
use Illuminate\Database\Seeder;

class ComplianceTestTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Evidence Malware Scan Check',
                'description' => 'Verifies that uploaded evidence files pass ClamAV malware scan. Detects any file returned as infected by the antivirus pipeline.',
                'integration_type' => 'n8n',
                'test_type' => 'Automated',
                'sla_days' => 1,
                'check_expression' => 'current_score >= threshold',
            ],
            [
                'name' => 'AI Analysis Completion Check',
                'description' => 'Verifies that Gemini AI analysis completed successfully for all uploaded evidence within the expected time window.',
                'integration_type' => 'n8n',
                'test_type' => 'Automated',
                'sla_days' => 1,
                'check_expression' => 'current_score >= threshold',
            ],
            [
                'name' => 'Evidence Processing SLA Check',
                'description' => 'Detects evidence files that were not processed through the n8n pipeline within the defined SLA window.',
                'integration_type' => 'n8n',
                'test_type' => 'Automated',
                'sla_days' => 1,
                'check_expression' => 'current_score >= threshold',
            ],
            [
                'name' => 'Malware Detection Alert',
                'description' => 'Flags any evidence file that was returned as infected by ClamAV scanning. Immediate remediation required.',
                'integration_type' => 'n8n',
                'test_type' => 'Automated',
                'sla_days' => null,
                'check_expression' => 'current_score >= threshold',
            ],
            [
                'name' => 'Evidence Upload Freshness',
                'description' => 'Checks that evidence files are uploaded at a minimum cadence. Detects stale or stalled evidence collection.',
                'integration_type' => 'n8n',
                'test_type' => 'Automated',
                'sla_days' => 7,
                'check_expression' => 'current_score >= threshold',
            ],
        ];

        foreach ($templates as $template) {
            ComplianceTestTemplate::firstOrCreate(
                ['name' => $template['name'], 'integration_type' => $template['integration_type']],
                $template
            );
        }
    }
}
