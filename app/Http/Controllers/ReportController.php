<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ReportGenerationService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportGenerationService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function generate(Project $project)
    {
        $this->authorize('view', $project);
        return $this->reportService->generate($project, 'pci_dss_roc');
    }
}
