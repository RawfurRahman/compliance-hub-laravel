<?php

// In app/Http/Controllers/ProjectController.php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query();

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
            'module_type' => 'required|string|in:pci_dss,iso_27001,swift_csp,vapt',
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'module_type' => $validated['module_type'],
            'user_id' => Auth::id(),
        ]);

        if ($project->module_type === 'pci_dss') {
            $project->pciDssDetails()->create([]);
        }

        if ($project->module_type === 'pci_dss') {
            return Redirect::route('pci.show', $project)->with('success', 'Project created successfully!');
        }
        
        return Redirect::route('projects.index')->with('success', 'Project created successfully!');
    }
}
