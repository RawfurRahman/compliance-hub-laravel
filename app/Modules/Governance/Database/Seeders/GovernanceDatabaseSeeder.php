<?php

namespace App\Modules\Governance\Database\Seeders;

use App\Models\User;
use App\Modules\Governance\Models\Domain;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyVersion;
use App\Modules\Governance\Models\PolicyWaiver;
use App\Modules\Governance\Models\PolicyException;
use App\Modules\Governance\Models\OwnershipMatrix;
use App\Modules\Governance\Models\Stakeholder;
use App\Modules\Governance\Models\SLARule;
use Illuminate\Database\Seeder;

class GovernanceDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'governance-admin'],
            [
                'email' => 'governance-admin@compliance-hub.test',
                'password' => 'password',
                'is_verified' => true,
            ]
        );

        $domains = [
            ['name' => 'Information Security', 'slug' => 'information-security', 'description' => 'Policies related to information security management and controls.'],
            ['name' => 'Data Privacy', 'slug' => 'data-privacy', 'description' => 'Policies governing data protection and privacy compliance.'],
            ['name' => 'Operational Resilience', 'slug' => 'operational-resilience', 'description' => 'Policies ensuring continued operations during disruptions.'],
            ['name' => 'Business Continuity', 'slug' => 'business-continuity', 'description' => 'Policies for business continuity and disaster recovery planning.'],
            ['name' => 'IT Governance', 'slug' => 'it-governance', 'description' => 'Policies for IT service management and governance.'],
        ];

        foreach ($domains as $data) {
            Domain::firstOrCreate(['slug' => $data['slug']], $data);
        }

        $publishedPolicy = Policy::create([
            'domain_id' => Domain::where('slug', 'information-security')->first()->id,
            'title' => 'Information Security Policy',
            'slug' => 'information-security-policy',
            'policy_number' => 'GOV-POL-0001',
            'description' => 'Defines the framework for protecting information assets across the organization.',
            'status' => 'published',
            'effective_date' => now()->toDateString(),
            'published_at' => now(),
            'owner_user_id' => $admin->id,
            'department' => 'Security',
            'current_version' => 2,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        PolicyVersion::create([
            'policy_id' => $publishedPolicy->id,
            'version_number' => 1,
            'title' => 'Information Security Policy v1',
            'content' => "**Information Security Policy**\n\nThis policy establishes the security framework for the organization...\n\n## Scope\nAll employees, contractors, and third-party users.\n\n## Policy\n1. Access control shall be enforced.\n2. Data shall be classified.\n3. Incident response procedures shall be maintained.",
            'change_summary' => 'Initial version',
            'status' => 'published',
            'effective_date' => now()->subMonths(6)->toDateString(),
            'created_by' => $admin->id,
        ]);

        PolicyVersion::create([
            'policy_id' => $publishedPolicy->id,
            'version_number' => 2,
            'title' => 'Information Security Policy v2',
            'content' => "**Information Security Policy**\n\nThis policy establishes the security framework for the organization...\n\n## Scope\nAll employees, contractors, vendors, and third-party users.\n\n## Policy\n1. Access control shall be enforced using least privilege.\n2. Data shall be classified per the Data Classification Policy.\n3. Incident response procedures shall be tested annually.\n4. Security awareness training is mandatory quarterly.",
            'change_summary' => 'Updated scope and added quarterly training requirement.',
            'status' => 'published',
            'effective_date' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);

        $reviewPolicy = Policy::create([
            'domain_id' => Domain::where('slug', 'data-privacy')->first()->id,
            'title' => 'Data Classification Policy',
            'slug' => 'data-classification-policy',
            'policy_number' => 'GOV-POL-0002',
            'description' => 'Defines data classification levels and handling requirements.',
            'status' => 'under_review',
            'effective_date' => null,
            'owner_user_id' => $admin->id,
            'department' => 'Compliance',
            'current_version' => 1,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        PolicyVersion::create([
            'policy_id' => $reviewPolicy->id,
            'version_number' => 1,
            'title' => 'Data Classification Policy',
            'content' => "**Data Classification Policy**\n\n## Classification Levels\n- Public: Information that can be freely disclosed.\n- Internal: Information for internal use only.\n- Confidential: Sensitive information requiring protection.\n- Restricted: Highly sensitive information with legal/compliance requirements.\n\n## Handling Requirements\nEach classification level has specific handling, storage, and transmission requirements.",
            'change_summary' => 'Initial version',
            'status' => 'under_review',
            'created_by' => $admin->id,
        ]);

        $draftPolicy = Policy::create([
            'domain_id' => Domain::where('slug', 'business-continuity')->first()->id,
            'title' => 'Business Continuity Plan Policy',
            'slug' => 'business-continuity-plan-policy',
            'policy_number' => 'GOV-POL-0003',
            'description' => 'Governs the development, maintenance, and testing of business continuity plans.',
            'status' => 'draft',
            'effective_date' => null,
            'owner_user_id' => $admin->id,
            'department' => 'Operations',
            'current_version' => 1,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        PolicyVersion::create([
            'policy_id' => $draftPolicy->id,
            'version_number' => 1,
            'title' => 'Business Continuity Plan Policy',
            'content' => "**Business Continuity Plan Policy**\n\n## Requirements\n1. Each business unit must maintain a BCP.\n2. BCPs must be reviewed annually.\n3. Testing must occur at least once per year.\n\n## Roles and Responsibilities\nTBD",
            'change_summary' => 'Draft version',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $waiver = PolicyWaiver::create([
            'policy_id' => $publishedPolicy->id,
            'title' => 'Legacy System Compliance Waiver',
            'description' => 'Temporary waiver for legacy HR system that cannot enforce MFA.',
            'justification' => 'The legacy HR system is scheduled for decommission in Q3 and cannot be modified to support MFA. Compensating controls include network segmentation and IP whitelisting.',
            'requested_by' => $admin->id,
            'approved_by' => $admin->id,
            'status' => 'approved',
            'effective_date' => now()->toDateString(),
            'expires_at' => now()->addMonths(6)->toDateString(),
            'department' => 'HR',
            'compensating_controls' => '1. Network segmentation isolating the HR system.\n2. IP whitelisting restricting access to authorized staff only.\n3. Enhanced logging and monitoring.',
        ]);

        PolicyException::create([
            'policy_id' => $reviewPolicy->id,
            'title' => 'Customer Data Access Exception',
            'description' => 'Exception to allow support team to view customer PII for troubleshooting.',
            'justification' => 'Support team requires read access to customer PII for incident resolution. All access is logged and audited monthly.',
            'requested_by' => $admin->id,
            'status' => 'pending',
            'effective_date' => now()->toDateString(),
            'expires_at' => now()->addMonths(3)->toDateString(),
            'department' => 'Support',
            'risk_acceptance' => 'Risk accepted by CISO. Monthly audit reviews in place.',
        ]);

        OwnershipMatrix::create([
            'policy_id' => $publishedPolicy->id,
            'user_id' => $admin->id,
            'role' => 'policy_owner',
            'is_primary' => true,
        ]);

        OwnershipMatrix::create([
            'policy_id' => $publishedPolicy->id,
            'user_id' => $admin->id,
            'role' => 'reviewer',
        ]);

        OwnershipMatrix::create([
            'policy_id' => $reviewPolicy->id,
            'user_id' => $admin->id,
            'role' => 'policy_owner',
            'is_primary' => true,
        ]);

        SLARule::create([
            'name' => 'Review Completion SLA',
            'description' => 'Policy reviews must be completed within 48 hours of assignment.',
            'trigger_event' => 'review_requested',
            'action_type' => 'review_completion',
            'sla_hours' => 48,
            'escalation_interval_hours' => 8,
        ]);

        SLARule::create([
            'name' => 'Approval Completion SLA',
            'description' => 'Policy approvals must be completed within 24 hours of request.',
            'trigger_event' => 'approval_requested',
            'action_type' => 'approval_completion',
            'sla_hours' => 24,
            'escalation_interval_hours' => 4,
        ]);

        SLARule::create([
            'name' => 'Publication SLA',
            'description' => 'Approved policies must be published within 72 hours.',
            'trigger_event' => 'policy_submitted',
            'action_type' => 'publication',
            'sla_hours' => 72,
            'escalation_interval_hours' => 12,
        ]);
    }
}
