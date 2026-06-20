<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\RequiredDocumentList;
use App\Services\RequiredDocumentListImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RequiredDocumentController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $lists = $project->requiredDocumentLists()->withCount('documents')->with('importedBy')->latest()->get();

        return $this->renderIndex($project, $lists, $lists->first());
    }

    public function import(Request $request, Project $project, RequiredDocumentListImportService $importer)
    {
        $this->authorize('update', $project);
        $this->ensureCanManage();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:docx,xlsx,xls,csv', 'max:20480'],
        ]);

        try {
            $list = $importer->import($request->file('file'), $project->id, $request->user()->id, $validated['name']);

            return redirect()->route('required-documents.index', $project)
                ->with('success', "{$list->documents()->count()} required evidence items were prepared for {$project->name}.");
        } catch (\Throwable $exception) {
            Log::error('Required document list import failed.', ['project_id' => $project->id, 'exception' => $exception]);

            return back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function show(Project $project, RequiredDocumentList $list)
    {
        $this->authorize('view', $project);
        $this->ensureListBelongsToProject($project, $list);
        $lists = $project->requiredDocumentLists()->withCount('documents')->with('importedBy')->latest()->get();

        return $this->renderIndex($project, $lists, $list->load('importedBy'));
    }

    public function destroy(Project $project, RequiredDocumentList $list)
    {
        $this->authorize('update', $project);
        $this->ensureCanManage();
        $this->ensureListBelongsToProject($project, $list);

        Storage::disk('local')->delete($list->source_file_path);
        $list->delete();

        return redirect()->route('required-documents.index', $project)->with('success', 'Required evidence list deleted.');
    }

    private function renderIndex(Project $project, $lists, ?RequiredDocumentList $activeList)
    {
        return view('required-documents.index', [
            'project' => $project,
            'lists' => $lists,
            'activeList' => $activeList,
            'documents' => $activeList ? $activeList->documents()->orderBy('sort_order')->get() : collect(),
        ]);
    }

    private function ensureListBelongsToProject(Project $project, RequiredDocumentList $list): void
    {
        abort_unless($list->project_id === $project->id, 404);
    }

    private function ensureCanManage(): void
    {
        abort_unless(auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Auditor'), 403);
    }
}
