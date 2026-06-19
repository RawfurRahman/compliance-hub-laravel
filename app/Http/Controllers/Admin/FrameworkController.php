<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Framework;
use Illuminate\Http\Request;

class FrameworkController extends Controller
{
    public function index()
    {
        $frameworks = Framework::orderBy('name')->get();
        return view('admin.frameworks.index', compact('frameworks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'required|string|max:100|unique:frameworks,slug',
            'version'     => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        Framework::create([
            'name'        => $request->name,
            'slug'        => $request->slug,
            'version'     => $request->version,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.frameworks.index')->with('success', 'Framework created successfully.');
    }

    public function update(Request $request, Framework $framework)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'required|string|max:100|unique:frameworks,slug,' . $framework->id,
            'version'     => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $framework->update([
            'name'        => $request->name,
            'slug'        => $request->slug,
            'version'     => $request->version,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.frameworks.index')->with('success', 'Framework updated successfully.');
    }

    public function destroy(Framework $framework)
    {
        $framework->delete();
        return redirect()->route('admin.frameworks.index')->with('success', 'Framework deleted successfully.');
    }
}
