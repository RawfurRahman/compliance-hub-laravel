<?php

namespace App\Modules\Compliance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\Compliance\Services\ComplianceFindingService;
use App\Modules\Compliance\Services\ComplianceQueryService;

class ComplianceDashboardController extends Controller
{
    public function __construct(
        private ComplianceFindingService $findingService,
        private ComplianceQueryService $queryService,
    ) {}

    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $counts = ComplianceFindingService::countByState($project->id);
        $byFramework = $this->queryService->complianceByFramework($project->id);
        $overdue = $this->queryService->overdueBySLA($project->id);

        return view('compliance.dashboard', compact('project', 'counts', 'byFramework', 'overdue'));
    }
}
