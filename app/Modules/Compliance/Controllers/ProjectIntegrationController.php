<?php

namespace App\Modules\Compliance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\Compliance\Models\ComplianceTestTemplate;
use App\Models\Integration;
use App\Services\IntegrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectIntegrationController extends Controller
{
    public function __construct(
        protected IntegrationService $integrationService
    ) {}

    public function index(Project $project): View
    {
        $availableTypes = ComplianceTestTemplate::selectRaw('integration_type, COUNT(*) as template_count')
            ->groupBy('integration_type')
            ->orderBy('integration_type')
            ->get();

        $connected = Integration::where('project_id', $project->id)
            ->withCount('complianceTests')
            ->orderBy('created_at', 'desc')
            ->get();

        $categories = [
            'n8n' => 'Workflow Automation',
        ];

        $connectedTypes = $connected->pluck('type')->toArray();

        return view('compliance.integrations.index', compact('project', 'availableTypes', 'connected', 'categories', 'connectedTypes'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'config' => 'nullable|json',
        ]);

        $integration = $this->integrationService->connectToProject(
            $validated,
            $project,
            $request->user()
        );

        $createdCount = $integration->complianceTests()->count();

        return redirect()->route('compliance.tests.index', $project)
            ->with('success', sprintf(
                'Connected %s integration "%s". %d compliance test(s) created automatically.',
                $validated['type'],
                $validated['name'],
                $createdCount
            ));
    }
}
