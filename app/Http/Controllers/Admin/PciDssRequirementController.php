<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PciDssRequirement;
use Illuminate\Http\Request;

class PciDssRequirementController extends Controller
{
    public function index()
    {
        $requirements = PciDssRequirement::orderBy('req_num')->paginate(50);
        return view('admin.requirements.index', compact('requirements'));
    }

    public function create()
    {
        return view('admin.requirements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'req_num'         => 'required|string|max:50|unique:pci_dss_requirements,req_num',
            'req_description' => 'required|string',
        ]);

        PciDssRequirement::create([
            'req_num'             => $request->req_num,
            'req_description'     => $request->req_description,
            'testing_procedures'  => $request->input('testing_procedures', []),
        ]);

        return redirect()->route('admin.requirements.index')->with('success', 'Requirement created successfully.');
    }

    public function edit(PciDssRequirement $requirement)
    {
        return view('admin.requirements.edit', compact('requirement'));
    }

    public function update(Request $request, PciDssRequirement $requirement)
    {
        $request->validate([
            'req_num'         => 'required|string|max:50|unique:pci_dss_requirements,req_num,' . $requirement->id,
            'req_description' => 'required|string',
        ]);

        $requirement->update([
            'req_num'             => $request->req_num,
            'req_description'     => $request->req_description,
            'testing_procedures'  => $request->input('testing_procedures', []),
        ]);

        return redirect()->route('admin.requirements.index')->with('success', 'Requirement updated successfully.');
    }

    public function destroy(PciDssRequirement $requirement)
    {
        $requirement->delete();
        return redirect()->route('admin.requirements.index')->with('success', 'Requirement deleted successfully.');
    }
}
