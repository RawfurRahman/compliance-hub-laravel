<?php

namespace App\Modules\Compliance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\Compliance\Models\ComplianceTest;
use App\Models\Framework;
use App\Modules\Compliance\Models\ControlMonitor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ComplianceTestController extends Controller
{
    public function index(Request $request, Project $project): View
    {
        $viewMode = $request->query('view', 'all');

        $query = ComplianceTest::query()
            ->with(['ownerUser', 'frameworkLinks.framework', 'failures']);
            
        $this->applyFilters($query, $request);

        if ($viewMode === 'by_framework') {
            $allTests = $query->orderBy('name')->get();
            $tests = $allTests;
        } else {
            $tests = $query->orderBy('name')->paginate(20);
        }
        
        $summary = $this->getSummaryData($project);
        
        $frameworks = Framework::where('is_active', true)->get();

        $frameworkGroups = collect();
        if ($viewMode === 'by_framework') {
            $frameworkGroups = $frameworks->map(function ($fw) use ($allTests) {
                $fwTests = $allTests->filter(fn ($t) =>
                    $t->frameworkLinks->contains('framework_id', $fw->id)
                )->values();

                $total = $fwTests->count();
                $passing = $fwTests->where('status', 'Passing')->count();

                return (object) [
                    'framework'    => $fw,
                    'tests'        => $fwTests,
                    'total'        => $total,
                    'passing'      => $passing,
                    'failing'      => $fwTests->where('status', 'Needs Remediation')->count(),
                    'overdue'      => $fwTests->where('status', 'Overdue')->count(),
                    'due_soon'     => $fwTests->where('status', 'Due Soon')->count(),
                    'not_yet_run'  => $fwTests->where('status', 'Not Yet Run')->count(),
                    'pass_rate'    => $total > 0 ? round(($passing / $total) * 100, 1) : 0,
                ];
            })->filter(fn ($g) => $g->total > 0)->values();
        }

        $filterData = $request->only(['framework_id', 'owner_id', 'status', 'test_type']);
        $users = User::orderBy('name')->get();
        
        return view('compliance.tests.index', compact(
            'project', 'tests', 'summary', 'frameworks', 'viewMode', 'frameworkGroups', 'filterData', 'users'
        ));
    }
    
    public function create(Project $project): View
    {
        $frameworks = Framework::where('is_active', true)->get();
        $users = User::orderBy('name')->get();
        $controlMonitors = ControlMonitor::get();
        
        return view('compliance.tests.create', compact(
            'project', 'frameworks', 'users', 'controlMonitors'
        ));
    }
    
    public function show(Project $project, int $testId): View
    {
        $test = ComplianceTest::with(['ownerUser', 'frameworkLinks.framework', 'failures', 'controlMonitor', 'frameworkLinks.framework'])->findOrFail($testId);
        
        return view('compliance.tests.show', compact('project', 'test'));
    }
    
    public function edit(Project $project, int $testId): View
    {
        $test = ComplianceTest::with(['ownerUser', 'frameworkLinks.framework', 'failures', 'controlMonitor'])->findOrFail($testId);
        $frameworks = Framework::where('is_active', true)->get();
        $users = User::orderBy('name')->get();
        $controlMonitors = ControlMonitor::get();
        
        return view('compliance.tests.edit', compact('project', 'test', 'frameworks', 'users', 'controlMonitors'));
    }
    
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'owner_user_id' => 'required|exists:users,id',
            'team' => 'required|string|max:100',
            'test_type' => 'required|in:Automated,Manual',
            'sla_days' => 'nullable|integer|min:0',
            'status' => 'required|in:Passing,Overdue,Needs Remediation,Due Soon,Not Yet Run',
            'last_run_at' => 'nullable|date',
            'next_due_at' => 'nullable|date',
            'control_monitor_id' => 'nullable|exists:comp_control_monitors,id',
            'framework_ids' => 'required|array|min:1',
            'framework_ids.*' => 'exists:frameworks,id',
            'failing_entities' => 'nullable|array',
            'failing_entities.*.description' => 'required|string',
            'failing_entities.*.detected_at' => 'required|date',
            'failing_entities.*.resolved_at' => 'nullable|date',
        ]);
        
        $test = ComplianceTest::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'owner_user_id' => $validated['owner_user_id'],
            'team' => $validated['team'],
            'test_type' => $validated['test_type'],
            'sla_days' => $validated['sla_days'],
            'status' => $validated['status'],
            'last_run_at' => $validated['last_run_at'],
            'next_due_at' => $validated['next_due_at'],
            'control_monitor_id' => $validated['control_monitor_id'],
        ]);
        
        foreach ($validated['framework_ids'] as $frameworkId) {
            $test->frameworkLinks()->create([
                'framework_id' => $frameworkId,
                'resources_in_scope_count' => 0,
            ]);
        }
        
        if (!empty($validated['failing_entities'])) {
            foreach ($validated['failing_entities'] as $failureData) {
                $test->failures()->create([
                    'failing_entity_description' => $failureData['description'],
                    'detected_at' => $failureData['detected_at'],
                    'resolved_at' => $failureData['resolved_at'],
                ]);
            }
        }
        
        return redirect()->route('compliance.tests.index', $project)
            ->with('success', 'Compliance test created successfully');
    }
    
    public function update(Request $request, Project $project, int $testId): RedirectResponse
    {
        $test = ComplianceTest::findOrFail($testId);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'owner_user_id' => 'required|exists:users,id',
            'team' => 'required|string|max:100',
            'test_type' => 'required|in:Automated,Manual',
            'sla_days' => 'nullable|integer|min:0',
            'status' => 'required|in:Passing,Overdue,Needs Remediation,Due Soon,Not Yet Run',
            'last_run_at' => 'nullable|date',
            'next_due_at' => 'nullable|date',
            'control_monitor_id' => 'nullable|exists:comp_control_monitors,id',
            'framework_ids' => 'required|array|min:1',
            'framework_ids.*' => 'exists:frameworks,id',
            'failing_entities' => 'nullable|array',
            'failing_entities.*.id' => 'nullable|integer|min:1',
            'failing_entities.*.description' => 'required|string',
            'failing_entities.*.detected_at' => 'required|date',
            'failing_entities.*.resolved_at' => 'nullable|date',
        ]);
        
        $test->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'owner_user_id' => $validated['owner_user_id'],
            'team' => $validated['team'],
            'test_type' => $validated['test_type'],
            'sla_days' => $validated['sla_days'],
            'status' => $validated['status'],
            'last_run_at' => $validated['last_run_at'],
            'next_due_at' => $validated['next_due_at'],
            'control_monitor_id' => $validated['control_monitor_id'],
        ]);
        
        $test->frameworkLinks()->delete();
        foreach ($validated['framework_ids'] as $frameworkId) {
            $test->frameworkLinks()->create([
                'framework_id' => $frameworkId,
                'resources_in_scope_count' => 0,
            ]);
        }
        
        $existingFailureIds = $test->failures()->pluck('id')->toArray();
        $requestFailures = $validated['failing_entities'] ?? [];
        
        $idsToKeep = collect($requestFailures)->pluck('id')->filter();
        $test->failures()->whereNotIn('id', $idsToKeep)->delete();
        
        foreach ($requestFailures as $index => $failureData) {
            $failureData['compliance_test_id'] = $test->id;
            
            if (isset($failureData['id']) && in_array($failureData['id'], $existingFailureIds)) {
                $test->failures()->where('id', $failureData['id'])->update([
                    'failing_entity_description' => $failureData['description'],
                    'detected_at' => $failureData['detected_at'],
                    'resolved_at' => $failureData['resolved_at'],
                ]);
            } else {
                $test->failures()->create($failureData);
            }
        }
        
        return redirect()->route('compliance.tests.show', [$project, $test->id])
            ->with('success', 'Compliance test updated successfully');
    }
    
    public function destroy(Project $project, int $testId): RedirectResponse
    {
        $test = ComplianceTest::findOrFail($testId);
        $test->delete();
        
        return redirect()->route('compliance.tests.index', $project)
            ->with('success', 'Compliance test deleted successfully');
    }
    
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('framework_id')) {
            $query->whereHas('frameworkLinks', function ($q) use ($request) {
                $q->where('framework_id', $request->framework_id);
            });
        }
        
        if ($request->filled('owner_id')) {
            $query->where('owner_user_id', $request->owner_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('test_type')) {
            $query->where('test_type', $request->test_type);
        }
    }
    
    private function getSummaryData(Project $project): array
    {
        $tests = ComplianceTest::all();
        
        return [
            'total_tests' => $tests->count(),
            'passing_tests' => $tests->where('status', 'Passing')->count(),
            'passing_percentage' => $tests->count() > 0 
                ? round(($tests->where('status', 'Passing')->count() / $tests->count()) * 100, 1) 
                : 0,
            
            'automated_tests' => $tests->where('test_type', 'Automated')->count(),
            'manual_tests' => $tests->where('test_type', 'Manual')->count(),
            
            'overdue_tests' => $tests->where('status', 'Overdue')->count(),
            'due_soon_tests' => $tests->where('status', 'Due Soon')->count(),
            'needs_remediation_tests' => $tests->where('status', 'Needs Remediation')->count(),
            'not_yet_run_tests' => $tests->where('status', 'Not Yet Run')->count(),
            
            'total_failing_entities' => $tests->sum(function ($test) {
                return $test->failures()->whereNull('resolved_at')->count();
            }),
        ];
    }
}