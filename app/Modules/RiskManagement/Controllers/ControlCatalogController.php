<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Control;
use App\Models\Framework;
use App\Models\User;
use App\Modules\RiskManagement\Imports\ControlMappingSheetImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ControlCatalogController extends Controller
{
    public function index()
    {
        $controls = Control::with(['framework', 'controlOwner'])
            ->orderBy('code')
            ->orderBy('control_code')
            ->get();

        $frameworks = Framework::where('is_active', true)->orderBy('name')->get();
        $users = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Auditor']))->get();

        return view('admin.controls.index', compact('controls', 'frameworks', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'           => 'required|string|max:100|unique:controls,code',
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'framework_id'   => 'nullable|integer|exists:frameworks,id',
            'status'         => 'nullable|string|max:50',
            'effectiveness_score' => 'nullable|numeric|min:0|max:100',
            'control_owner_id'    => 'nullable|integer|exists:users,id',
        ]);

        $data['control_code'] = $data['code'];
        $data['name'] = $data['title'];
        $data['is_active'] = true;

        Control::create($data);

        return redirect()->route('admin.controls.index')->with('success', 'Control created.');
    }

    public function edit(Control $control)
    {
        $frameworks = Framework::where('is_active', true)->orderBy('name')->get();
        $users = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Auditor']))->get();

        $complianceTests = $control->complianceTests()->with('ownerUser')->orderBy('name')->get();
        $passingCount = $complianceTests->where('status', 'Passing')->count();
        $totalCount = $complianceTests->count();

        return view('admin.controls.edit', compact(
            'control', 'frameworks', 'users', 'complianceTests', 'passingCount', 'totalCount'
        ));
    }

    public function update(Request $request, Control $control)
    {
        $data = $request->validate([
            'code'           => "required|string|max:100|unique:controls,code,{$control->id}",
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'framework_id'   => 'nullable|integer|exists:frameworks,id',
            'status'         => 'nullable|string|max:50',
            'effectiveness_score' => 'nullable|numeric|min:0|max:100',
            'control_owner_id'    => 'nullable|integer|exists:users,id',
        ]);

        $data['control_code'] = $data['code'];
        $data['name'] = $data['title'];

        $control->update($data);

        return redirect()->route('admin.controls.index')->with('success', 'Control updated.');
    }

    public function destroy(Control $control)
    {
        $control->delete();
        return redirect()->route('admin.controls.index')->with('success', 'Control deleted.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'       => 'required|file|mimes:xlsx,xls,csv',
            'framework'  => 'required|string|exists:frameworks,slug',
        ]);

        $import = new ControlMappingSheetImport($request->framework);
        Excel::import($import, $request->file('file'));

        return redirect()->route('admin.frameworks.controls.index', ['framework' => $request->framework])
            ->with('success', 'Control Mapping sheet imported successfully.');
    }
}
