<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Framework;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query();

        if (!Auth::user()->hasRole('Admin')) {
            $userId = Auth::id();
            // If user is a sub-user, they inherit access to their parent's assigned projects
            $targetUserId = Auth::user()->parent_id ?? $userId;
            
            $query->whereHas('assignedUsers', function ($q) use ($targetUserId) {
                $q->where('user_id', $targetUserId);
            });
        }

        if ($request->has('module')) {
            $query->where('module_type', $request->module);
        }

        $projects = $query->latest()->get();
        
        return view('projects.index', compact('projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'module_type' => 'required|string|exists:frameworks,slug',
            'auditors' => 'nullable|array',
            'auditors.*' => 'exists:users,id',
            'customers' => 'nullable|array',
            'customers.*' => 'exists:users,id',
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'module_type' => $validated['module_type'],
            'user_id' => Auth::id(), // Creator
        ]);

        if (Auth::user()->hasRole('Admin')) {
            $userIds = array_merge(
                $request->input('auditors', []),
                $request->input('customers', [])
            );
            $project->assignedUsers()->sync($userIds);
        } else {
            // If created by a non-admin, assign themselves to the project so they can view it.
            $project->assignedUsers()->attach(Auth::id());
        }

        if ($project->module_type === 'pci_dss') {
            $project->pciDssDetails()->create([]);
        }

        if ($project->module_type === 'pci_dss') {
            return Redirect::route('pci.show', $project)->with('success', 'Project created successfully!');
        }
        
        return Redirect::route('projects.index')->with('success', 'Project created successfully!');
    }

    /**
     * Return project data as JSON for the edit modal (Admin only).
     */
    public function edit(Project $project)
    {
        abort_if(!Auth::user()->hasRole('Admin'), 403);

        $project->load('assignedUsers.roles');

        $auditorIds  = $project->assignedUsers->filter(fn($u) => optional($u->roles->first())->name === 'Auditor')->pluck('id');
        $customerIds = $project->assignedUsers->filter(fn($u) => optional($u->roles->first())->name === 'Customer')->pluck('id');

        return response()->json([
            'id'          => $project->id,
            'name'        => $project->name,
            'module_type' => $project->module_type,
            'auditor_ids' => $auditorIds->values(),
            'customer_ids'=> $customerIds->values(),
        ]);
    }

    /**
     * Update project name and/or assigned users (Admin only).
     */
    public function update(Request $request, Project $project)
    {
        abort_if(!Auth::user()->hasRole('Admin'), 403);

        $request->validate([
            'name'        => 'required|string|max:255',
            'auditors'    => 'nullable|array',
            'auditors.*'  => 'exists:users,id',
            'customers'   => 'nullable|array',
            'customers.*' => 'exists:users,id',
        ]);

        $project->update(['name' => $request->name]);

        $userIds = array_merge(
            $request->input('auditors', []),
            $request->input('customers', [])
        );

        $project->assignedUsers()->sync($userIds);

        return Redirect::route('projects.index')->with('success', 'Project updated successfully!');
    }
}
