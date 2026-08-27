<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Control;
use App\Models\Framework;
use App\Models\User;
use App\Modules\RiskManagement\Imports\ControlMappingSheetImport;
use App\Services\UploadRejectionLogger;
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
            'code' => 'required|string|max:100|unique:controls,code',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'framework_id' => 'nullable|integer|exists:frameworks,id',
            'status' => 'nullable|string|max:50',
            'effectiveness_score' => 'nullable|numeric|min:0|max:100',
            'control_owner_id' => 'nullable|integer|exists:users,id',
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
            'code' => "required|string|max:100|unique:controls,code,{$control->id}",
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'framework_id' => 'nullable|integer|exists:frameworks,id',
            'status' => 'nullable|string|max:50',
            'effectiveness_score' => 'nullable|numeric|min:0|max:100',
            'control_owner_id' => 'nullable|integer|exists:users,id',
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
        $formats = implode(', ', config('uploads.imports.extensions'));

        $validated = UploadRejectionLogger::validate(
            $request,
            [
                'file' => [
                    'required',
                    'file',
                    'mimes:'.implode(',', config('uploads.imports.extensions')),
                    'mimetypes:'.implode(',', config('uploads.imports.mimetypes')),
                    'max:'.(int) config('uploads.imports.max_size_kb'),
                ],
                'framework' => 'required|string|exists:frameworks,slug',
            ],
            'data-import.rejected',
            [
                'file.mimes' => "The file must be one of the accepted import formats: {$formats}.",
                'file.mimetypes' => "The file content does not match the accepted import formats. Allowed: {$formats}.",
                'file.max' => 'The file may not be larger than '.((int) config('uploads.imports.max_size_kb') / 1024).' MB.',
            ],
        );

        $import = new ControlMappingSheetImport($validated['framework']);
        Excel::import($import, $request->file('file'));

        return redirect()->route('admin.frameworks.controls.index', ['framework' => $request->framework])
            ->with('success', 'Control Mapping sheet imported successfully.');
    }
}
