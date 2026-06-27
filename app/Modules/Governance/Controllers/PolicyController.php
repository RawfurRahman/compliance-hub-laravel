<?php

namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Requests\StorePolicyRequest;
use App\Modules\Governance\Requests\UpdatePolicyRequest;
use App\Modules\Governance\Services\PolicyService;
use App\Modules\Governance\Services\PolicyVersionService;
use App\Services\DirectEvidenceAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PolicyController extends Controller
{
    public function __construct(
        private PolicyService $policyService,
        private PolicyVersionService $versionService,
    ) {}

    public function showBulkUploadForm(Project $project)
    {
        return view('governance.policies.bulk-upload', compact('project'));
    }

    public function processBulkUpload(Request $request, Project $project)
    {
        $request->validate([
            'files'   => 'required|array|min:1|max:20',
            'files.*' => 'required|file|mimes:pdf|max:20480',
        ]);

        $request->session()->forget('policy_bulk_import');

        $gemini = app(DirectEvidenceAnalysisService::class);
        $prompt = <<<PROMPT
You are a GRC policy analyst. Analyze the provided PDF document and extract the following fields in strict JSON.
If a field cannot be determined, use null.

Fields:
- title: The policy title or document name
- description: A short 1-2 sentence description of what this policy covers
- approval_date: The approval date in YYYY-MM-DD format, or null
- approver: The name of the approving person, or null

Return ONLY raw JSON, no markdown, no code blocks:
{"title": "...", "description": "...", "approval_date": "YYYY-MM-DD", "approver": "..."}
PROMPT;

        $imports = [];

        foreach ($request->file('files') as $i => $file) {
            $tempPath = $file->store('temp/policy-import', 'local');
            $fileContents = Storage::disk('local')->get($tempPath);
            $mimeType = $file->getMimeType() ?: 'application/pdf';

            $extracted = $gemini->extractFromPdf($fileContents, $mimeType, $prompt);

            $title = $extracted['title'] ?? null;
            $description = $extracted['description'] ?? null;
            $approvalDate = $extracted['approval_date'] ?? null;
            $approver = $extracted['approver'] ?? null;

            $allPopulated = $title && $description && $approvalDate && $approver;

            $duplicate = null;
            if ($title) {
                $duplicate = Policy::where('title', $title)->first();
            }

            $imports[] = [
                'id'                       => $i,
                'temp_path'                => $tempPath,
                'original_filename'        => $file->getClientOriginalName(),
                'extracted_title'          => $title ?? '',
                'extracted_description'    => $description ?? '',
                'extracted_approval_date'  => $approvalDate ?? '',
                'extracted_approver'       => $approver ?? '',
                'all_fields_populated'     => $allPopulated,
                'duplicate_policy_id'      => $duplicate?->id,
                'duplicate_policy_number'  => $duplicate?->policy_number,
                'duplicate_title'          => $duplicate?->title,
            ];
        }

        $request->session()->put('policy_bulk_import', $imports);

        return redirect()->route('governance.policies.bulk.review', $project)
            ->with('success', count($imports) . ' file(s) processed. Review the extracted data below.');
    }

    public function showBulkReview(Project $project)
    {
        $imports = session('policy_bulk_import', []);

        if (empty($imports)) {
            return redirect()->route('governance.policies.bulk', $project)
                ->with('error', 'No imported files found. Please upload PDFs first.');
        }

        return view('governance.policies.bulk-review', compact('project', 'imports'));
    }

    public function confirmBulkImport(Request $request, Project $project)
    {
        $imports = session('policy_bulk_import', []);
        if (empty($imports)) {
            return redirect()->route('governance.policies.bulk', $project)
                ->with('error', 'Session expired. Please upload the files again.');
        }

        $confirmedIds = $request->input('confirmed', []);
        if (!is_array($confirmedIds) || empty($confirmedIds)) {
            return back()->with('error', 'No files selected for import.');
        }

        $created = 0;

        foreach ($imports as &$item) {
            if (!in_array($item['id'], $confirmedIds)) {
                continue;
            }

            $title = $request->input("items.{$item['id']}.title", $item['extracted_title']);
            $description = $request->input("items.{$item['id']}.description", $item['extracted_description']);
            $approvalDate = $request->input("items.{$item['id']}.approval_date", $item['extracted_approval_date']);
            $approver = $request->input("items.{$item['id']}.approver", $item['extracted_approver']);

            $policy = Policy::create([
                'title'          => $title ?: 'Untitled Policy',
                'description'    => $description ?: null,
                'effective_date' => $approvalDate ?: null,
                'status'         => 'draft',
                'is_active'      => true,
            ]);

            $this->versionService->createVersion($policy, [
                'title'          => $policy->title,
                'content'        => "Imported via bulk upload from {$item['original_filename']}.\n\nAI-extracted approver: " . ($approver ?: 'Not specified'),
                'change_summary' => 'Initial version (bulk import)',
                'status'         => 'draft',
            ]);

            if ($item['temp_path'] && Storage::disk('local')->exists($item['temp_path'])) {
                Storage::disk('local')->delete($item['temp_path']);
            }

            $item['confirmed'] = true;
            $created++;
        }
        unset($item);

        $remaining = collect($imports)->whereNull('confirmed')->count();
        if ($remaining === 0) {
            $request->session()->forget('policy_bulk_import');
        } else {
            $request->session()->put('policy_bulk_import', $imports);
        }

        return redirect()->route('governance.policies.index', $project)
            ->with('success', "{$created} policy/policies created successfully.");
    }

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'domain_id', 'owner_user_id', 'search']);
        $policies = $this->policyService->list($filters);

        if ($request->expectsJson()) {
            return response()->json(['data' => $policies]);
        }

        return view('governance.policies.index', compact('policies'));
    }

    public function create()
    {
        return view('governance.policies.form', ['policy' => null]);
    }

    public function store(StorePolicyRequest $request)
    {
        $policy = $this->policyService->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['data' => $policy, 'message' => 'Policy created.'], 201);
        }

        return redirect()
            ->route('governance.policies.show', $policy)
            ->with('success', "Policy {$policy->policy_number} created.");
    }

    public function show(Policy $policy)
    {
        $policy->load(['domain', 'ownerUser', 'versions', 'reviews.reviewer', 'approvals.approver', 'ownershipMatrix.user', 'stakeholders.user']);

        return view('governance.policies.show', compact('policy'));
    }

    public function edit(Policy $policy)
    {
        $this->authorize('update', $policy);

        return view('governance.policies.form', compact('policy'));
    }

    public function update(UpdatePolicyRequest $request, Policy $policy)
    {
        $this->authorize('update', $policy);

        $policy = $this->policyService->update($policy, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['data' => $policy, 'message' => 'Policy updated.']);
        }

        return redirect()
            ->route('governance.policies.show', $policy)
            ->with('success', "Policy {$policy->policy_number} updated.");
    }

    public function destroy(Request $request, Policy $policy)
    {
        $this->authorize('delete', $policy);

        $this->policyService->delete($policy);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Policy deleted.']);
        }

        return redirect()
            ->route('governance.policies.index')
            ->with('success', "Policy {$policy->policy_number} deleted.");
    }

    public function submitForReview(Request $request, Policy $policy)
    {
        $this->authorize('review', $policy);

        $request->validate(['comment' => 'nullable|string|max:2000']);

        $policy = $this->policyService->submitForReview($policy, $request->comment);

        return response()->json([
            'data' => $policy,
            'message' => "Policy {$policy->policy_number} submitted for review.",
        ]);
    }

    public function publish(Request $request, Policy $policy)
    {
        $this->authorize('publish', $policy);

        $request->validate([
            'effective_date' => 'required|date',
            'method' => 'sometimes|in:auto,manual',
        ]);

        $policy = $this->policyService->publish(
            $policy,
            $request->effective_date,
            $request->method ?? 'manual'
        );

        return response()->json([
            'data' => $policy,
            'message' => "Policy {$policy->policy_number} published.",
        ]);
    }

    public function deprecate(Request $request, Policy $policy)
    {
        $request->validate(['reason' => 'required|string|max:2000']);

        $policy = $this->policyService->deprecate($policy, $request->reason);

        return response()->json([
            'data' => $policy,
            'message' => "Policy {$policy->policy_number} deprecated.",
        ]);
    }

    public function archive(Policy $policy)
    {
        $policy = $this->policyService->archive($policy);

        return response()->json([
            'data' => $policy,
            'message' => "Policy {$policy->policy_number} archived.",
        ]);
    }

    public function reactivate(Policy $policy)
    {
        $policy = $this->policyService->reactivate($policy);

        return response()->json([
            'data' => $policy,
            'message' => "Policy {$policy->policy_number} reactivated.",
        ]);
    }

    public function expire(Policy $policy)
    {
        $policy = $this->policyService->expire($policy);

        return response()->json([
            'data' => $policy,
            'message' => "Policy {$policy->policy_number} expired.",
        ]);
    }

    public function versions(Request $request, Policy $policy)
    {
        $versions = $this->versionService->getVersionHistory($policy);

        if ($request->expectsJson()) {
            return response()->json(['data' => $versions]);
        }

        return view('governance.policies.versions', compact('policy', 'versions'));
    }
}
