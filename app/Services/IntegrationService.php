<?php

namespace App\Services;

use App\Models\Integration;
use App\Models\Project;
use App\Models\User;
use App\Modules\Compliance\Models\ComplianceTest;
use App\Modules\Compliance\Models\ComplianceTestTemplate;
use Illuminate\Support\Collection;

class IntegrationService
{
    public function connectToProject(array $data, Project $project, User $user): Integration
    {
        $integration = Integration::create([
            'project_id' => $project->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'config' => $data['config'] ?? null,
            'is_active' => true,
        ]);

        $this->autoCreateTests($integration, $project, $user);

        return $integration;
    }

    public function autoCreateTests(Integration $integration, Project $project, User $user): Collection
    {
        $templates = ComplianceTestTemplate::where('integration_type', $integration->type)->get();

        $activeFrameworks = \App\Models\Framework::where('is_active', true)->get();

        $created = collect();

        foreach ($templates as $template) {
            $test = ComplianceTest::create([
                'name' => $template->name,
                'description' => $template->description,
                'owner_user_id' => $user->id,
                'team' => 'Security Team',
                'test_type' => $template->test_type,
                'sla_days' => $template->sla_days,
                'status' => 'Not Yet Run',
                'last_run_at' => null,
                'next_due_at' => $template->sla_days ? now()->addDays($template->sla_days) : null,
                'integration_id' => $integration->id,
                'control_monitor_id' => null,
            ]);

            foreach ($activeFrameworks as $framework) {
                $test->frameworkLinks()->create([
                    'framework_id' => $framework->id,
                    'resources_in_scope_count' => 0,
                ]);
            }

            $created->push($test);
        }

        return $created;
    }
}
