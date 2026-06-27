<?php

namespace App\Http\Controllers;

use App\Modules\Compliance\Models\ComplianceTest;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyApproval;
use App\Modules\Governance\Models\PolicyReview;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\VendorAssessment;
use App\Modules\TrustCenter\Models\TrustCenterAccessRequest;
use App\Modules\TrustCenter\Models\TrustCenterQuestionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class MySecurityTasksController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tasks = [];

        foreach ($this->complianceTests($user) as $item) {
            $tasks[] = $item;
        }
        foreach ($this->policies($user) as $item) {
            $tasks[] = $item;
        }
        foreach ($this->policyApprovals($user) as $item) {
            $tasks[] = $item;
        }
        foreach ($this->policyReviews($user) as $item) {
            $tasks[] = $item;
        }
        foreach ($this->riskRegisters($user) as $item) {
            $tasks[] = $item;
        }
        foreach ($this->vendorAssessments($user) as $item) {
            $tasks[] = $item;
        }
        if ($user->can('is-admin')) {
            foreach ($this->trustCenterRequests($user) as $item) {
                $tasks[] = $item;
            }
            foreach ($this->trustCenterQuestionnaires($user) as $item) {
                $tasks[] = $item;
            }
        }

        $priorityOrder = ['overdue' => 0, 'due_soon' => 1, 'pending' => 2, 'attention' => 3];
        usort($tasks, fn ($a, $b) => ($priorityOrder[$a['priority']] ?? 9) - ($priorityOrder[$b['priority']] ?? 9));

        $grouped = collect($tasks)->groupBy('group');

        return view('my-security-tasks.index', compact('grouped', 'tasks'));
    }

    private function complianceTests($user): array
    {
        $tests = ComplianceTest::where('owner_user_id', $user->id)
            ->whereIn('status', ['Overdue', 'Needs Remediation', 'Due Soon', 'Not Yet Run'])
            ->with('integration.project')
            ->get();

        $items = [];
        foreach ($tests as $test) {
            $project = $test->integration?->project;
            $items[] = [
                'type' => 'Compliance Test',
                'group' => 'Compliance Tests',
                'title' => $test->name,
                'description' => $test->description ?: 'Status: ' . $test->status,
                'status' => $test->status,
                'priority' => $test->status === 'Overdue' ? 'overdue' : ($test->status === 'Due Soon' ? 'due_soon' : 'attention'),
                'url' => $project ? route('compliance.tests.show', [$project, $test]) : '#',
            ];
        }
        return $items;
    }

    private function policies($user): array
    {
        $policies = Policy::where('owner_user_id', $user->id)
            ->whereIn('status', ['draft', 'under_review', 'approved'])
            ->get();

        $items = [];
        foreach ($policies as $policy) {
            $items[] = [
                'type' => 'Policy',
                'group' => 'Policies',
                'title' => $policy->title ?: 'Policy #' . $policy->id,
                'description' => 'Status: ' . ucwords(str_replace('_', ' ', $policy->status)),
                'status' => $policy->status,
                'priority' => 'attention',
                'url' => $policy->project ? route('governance.policies.show', [$policy->project, $policy]) : '#',
            ];
        }
        return $items;
    }

    private function policyApprovals($user): array
    {
        $approvals = PolicyApproval::where('approver_user_id', $user->id)
            ->where('status', 'pending')
            ->with('policy.project')
            ->get();

        $items = [];
        foreach ($approvals as $approval) {
            $items[] = [
                'type' => 'Policy Approval',
                'group' => 'Policy Approvals',
                'title' => 'Awaiting your approval',
                'description' => $approval->policy?->title ?: 'Policy #' . $approval->policy_id,
                'status' => 'Pending',
                'priority' => 'pending',
                'url' => $approval->policy?->project ? route('governance.policies.show', [$approval->policy->project, $approval->policy]) : '#',
            ];
        }
        return $items;
    }

    private function policyReviews($user): array
    {
        $reviews = PolicyReview::where('reviewer_user_id', $user->id)
            ->where('status', 'pending')
            ->with('policy.project')
            ->get();

        $items = [];
        foreach ($reviews as $review) {
            $items[] = [
                'type' => 'Policy Review',
                'group' => 'Policy Reviews',
                'title' => 'Review pending',
                'description' => $review->policy?->title ?: 'Policy #' . $review->policy_id,
                'status' => 'Pending',
                'priority' => 'pending',
                'url' => $review->policy?->project ? route('governance.policies.show', [$review->policy->project, $review->policy]) : '#',
            ];
        }
        return $items;
    }

    private function riskRegisters($user): array
    {
        $risks = RiskRegister::where('owner_user_id', $user->id)
            ->whereIn('lifecycle_status', ['assessed', 'escalated', 'treated', 'monitoring'])
            ->get();

        $items = [];
        foreach ($risks as $risk) {
            $project = $risk->project;
            $items[] = [
                'type' => 'Risk Register',
                'group' => 'Risk Register Items',
                'title' => $risk->risk_title ?? $risk->risk_description ?? 'Risk #' . $risk->id,
                'description' => 'Lifecycle: ' . ucfirst($risk->lifecycle_status),
                'status' => $risk->lifecycle_status,
                'priority' => $risk->lifecycle_status === 'escalated' ? 'overdue' : 'attention',
                'url' => $project ? route('risk-register.edit', [$project, $risk]) : '#',
            ];
        }
        return $items;
    }

    private function vendorAssessments($user): array
    {
        $assessments = VendorAssessment::where('assessor_id', $user->id)
            ->whereNotIn('status', ['completed', 'failed'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->with('vendor')
            ->get();

        $items = [];
        foreach ($assessments as $assessment) {
            $project = $assessment->vendor?->project;
            $items[] = [
                'type' => 'Vendor Assessment',
                'group' => 'Vendor Assessments',
                'title' => 'Overdue: ' . ($assessment->vendor?->name ?? 'Vendor #' . $assessment->vendor_id),
                'description' => 'Due: ' . $assessment->due_date->format('M j, Y'),
                'status' => 'Overdue',
                'priority' => 'overdue',
                'url' => ($project && $assessment->vendor) ? route('vendors.assessments.detail', [$project, $assessment->vendor, $assessment]) : '#',
            ];
        }
        return $items;
    }

    private function trustCenterRequests($user): array
    {
        try {
            $requests = TrustCenterAccessRequest::where('status', 'Pending')
                ->with('trustCenter')
                ->get();
        } catch (\Exception $e) {
            Log::info('TrustCenterAccessRequest table not available: ' . $e->getMessage());
            return [];
        }

        $items = [];
        foreach ($requests as $req) {
            $items[] = [
                'type' => 'Access Request',
                'group' => 'Trust Center',
                'title' => 'Access request from ' . ($req->requester_email ?? 'unknown'),
                'description' => $req->trustCenter?->name ?? 'Trust Center',
                'status' => 'Pending',
                'priority' => 'pending',
                'url' => $req->trustCenter ? route('admin.trust-centers.requests', $req->trustCenter) : '#',
            ];
        }
        return $items;
    }

    private function trustCenterQuestionnaires($user): array
    {
        try {
            $questionnaires = TrustCenterQuestionnaire::where('status', 'Submitted')
                ->whereNull('responded_at')
                ->with('trustCenter')
                ->get();
        } catch (\Exception $e) {
            Log::info('TrustCenterQuestionnaire table not available: ' . $e->getMessage());
            return [];
        }

        $items = [];
        foreach ($questionnaires as $q) {
            $items[] = [
                'type' => 'Questionnaire',
                'group' => 'Trust Center',
                'title' => 'Questionnaire from ' . ($q->requester_email ?? 'unknown'),
                'description' => $q->trustCenter?->name ?? 'Trust Center',
                'status' => 'Submitted',
                'priority' => 'pending',
                'url' => $q->trustCenter ? route('admin.trust-centers.questionnaires', $q->trustCenter) : '#',
            ];
        }
        return $items;
    }
}
