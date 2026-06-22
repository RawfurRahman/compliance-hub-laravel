<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Imports\FrameworkControlImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FrameworkControlController extends Controller
{
    /**
     * Display a listing of the controls for the given framework.
     */
    public function index(Framework $framework)
    {
        $controls = FrameworkControl::where('framework_id', $framework->id)
            ->orderBy('control_id')
            ->get();

        return view('admin.frameworks.controls.index', compact('framework', 'controls'));
    }

    /**
     * Store a newly created control in storage.
     */
    public function store(Request $request, Framework $framework)
    {
        $request->validate([
            'control_id'              => 'required|string|max:50',
            'domain'                  => 'required|string|max:255',
            'requirement_description' => 'required|string',
            'required_evidence'       => 'nullable|string',
        ]);

        FrameworkControl::create([
            'framework_id'            => $framework->id,
            'control_id'              => $request->control_id,
            'domain'                  => $request->domain,
            'requirement_description' => $request->requirement_description,
            'required_evidence'       => $request->required_evidence,
        ]);

        return redirect()
            ->route('admin.frameworks.controls.index', $framework)
            ->with('success', 'Control created successfully.');
    }

    /**
     * Import controls in bulk from an Excel/CSV file.
     */
    public function import(Request $request, Framework $framework)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new FrameworkControlImport($framework->id), $request->file('file'));

            return redirect()
                ->route('admin.frameworks.controls.index', $framework)
                ->with('success', 'Controls imported successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.frameworks.controls.index', $framework)
                ->with('error', 'Failed to import controls: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified control from storage.
     */
    public function destroy(Framework $framework, FrameworkControl $control)
    {
        // Ensure the control belongs to the framework
        if ($control->framework_id !== $framework->id) {
            abort(403);
        }

        $control->delete();

        return redirect()
            ->route('admin.frameworks.controls.index', $framework)
            ->with('success', 'Control deleted successfully.');
    }
}
