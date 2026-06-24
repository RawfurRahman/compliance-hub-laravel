<?php

namespace App\Modules\Governance\Services;

use App\Models\ActivityLog;
use App\Modules\Governance\Events\PolicyApproved;
use App\Modules\Governance\Events\PolicyPublished;
use App\Modules\Governance\Events\PolicyReviewCompleted;
use App\Modules\Governance\Events\PolicySubmittedForReview;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PolicyService
{
    public function __construct(
        private PolicyVersionService $versionService,
    ) {}

    public function list(array $filters = []): Collection
    {
        $query = Policy::with(['domain', 'ownerUser', 'createdBy']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['domain_id'])) {
            $query->where('domain_id', $filters['domain_id']);
        }
        if (!empty($filters['owner_user_id'])) {
            $query->where('owner_user_id', $filters['owner_user_id']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('policy_number', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function findById(int $id): Policy
    {
        return Policy::with([
            'domain', 'ownerUser', 'createdBy', 'updatedBy',
            'versions', 'reviews.reviewer', 'approvals.approver',
            'ownershipMatrix.user', 'stakeholders.user',
        ])->findOrFail($id);
    }

    public function create(array $data): Policy
    {
        $policy = DB::transaction(function () use ($data) {
            $policy = Policy::create([
                'domain_id' => $data['domain_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'owner_user_id' => $data['owner_user_id'] ?? null,
                'department' => $data['department'] ?? null,
                'business_unit' => $data['business_unit'] ?? null,
                'status' => 'draft',
                'is_active' => true,
            ]);

            $this->versionService->createVersion($policy, [
                'title' => $policy->title,
                'content' => $data['content'] ?? '',
                'change_summary' => 'Initial version',
                'status' => 'draft',
            ]);

            return $policy->fresh();
        });

        $this->logActivity('policy_created', "Policy {$policy->policy_number} created", [
            'policy_id' => $policy->id,
            'policy_number' => $policy->policy_number,
            'title' => $policy->title,
        ]);

        return $policy;
    }

    public function update(Policy $policy, array $data): Policy
    {
        if (!$policy->isEditable()) {
            throw new \RuntimeException('Only draft or archived policies can be edited.');
        }

        $policy->update([
            'domain_id' => $data['domain_id'] ?? $policy->domain_id,
            'title' => $data['title'] ?? $policy->title,
            'description' => $data['description'] ?? $policy->description,
            'owner_user_id' => $data['owner_user_id'] ?? $policy->owner_user_id,
            'department' => $data['department'] ?? $policy->department,
            'business_unit' => $data['business_unit'] ?? $policy->business_unit,
        ]);

        $this->logActivity('policy_updated', "Policy {$policy->policy_number} updated", [
            'policy_id' => $policy->id,
            'changes' => $policy->getChanges(),
        ]);

        return $policy->fresh();
    }

    public function delete(Policy $policy): void
    {
        $policyNumber = $policy->policy_number;
        $policy->delete();

        $this->logActivity('policy_deleted', "Policy {$policyNumber} deleted", [
            'policy_id' => $policy->id,
            'policy_number' => $policyNumber,
        ]);
    }

    public function submitForReview(Policy $policy, ?string $comment = null): Policy
    {
        $this->assertCanTransition($policy, 'under_review');

        if ($policy->current_version === 0) {
            throw new \RuntimeException('Policy must have at least one version before review.');
        }

        $policy->update([
            'status' => 'under_review',
            'updated_by' => Auth::id(),
        ]);

        $policy->versions()->where('version_number', $policy->current_version)
            ->update(['status' => 'under_review']);

        PolicySubmittedForReview::dispatch($policy, Auth::id(), $comment);

        $this->logActivity('policy_submitted_for_review', "Policy {$policy->policy_number} submitted for review", [
            'policy_id' => $policy->id,
            'comment' => $comment,
        ]);

        return $policy->fresh();
    }

    public function returnToDraft(Policy $policy, string $reason): Policy
    {
        $this->assertCanTransition($policy, 'draft');

        $policy->update([
            'status' => 'draft',
            'updated_by' => Auth::id(),
        ]);

        $this->logActivity('policy_returned_to_draft', "Policy {$policy->policy_number} returned to draft", [
            'policy_id' => $policy->id,
            'reason' => $reason,
        ]);

        return $policy->fresh();
    }

    public function approve(Policy $policy, int $approverId, ?string $comments = null): Policy
    {
        $this->assertCanTransition($policy, 'approved');

        $policy->update([
            'status' => 'approved',
            'updated_by' => Auth::id(),
        ]);

        $approval = $policy->approvals()->create([
            'approver_user_id' => $approverId,
            'status' => 'approved',
            'comments' => $comments,
            'approved_at' => now(),
            'created_by' => Auth::id(),
        ]);

        PolicyApproved::dispatch($policy, $approval, $approverId);

        $this->logActivity('policy_approved', "Policy {$policy->policy_number} approved", [
            'policy_id' => $policy->id,
            'approver_id' => $approverId,
        ]);

        return $policy->fresh();
    }

    public function publish(Policy $policy, string $effectiveDate, string $method = 'manual'): Policy
    {
        $this->assertCanTransition($policy, 'published');

        if (empty($effectiveDate)) {
            throw new \RuntimeException('A published policy must have an effective date.');
        }

        $version = $policy->versions()
            ->where('version_number', $policy->current_version)
            ->first();

        if (!$version) {
            throw new \RuntimeException('No version found to publish.');
        }

        $policy->update([
            'status' => 'published',
            'effective_date' => $effectiveDate,
            'published_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        $version->update(['status' => 'published', 'effective_date' => $effectiveDate]);

        $publication = $policy->publications()->create([
            'policy_version_id' => $version->id,
            'published_by' => Auth::id(),
            'method' => $method,
            'audience' => 'all',
            'published_at' => now(),
        ]);

        PolicyPublished::dispatch($policy, $version, Auth::id());

        $this->logActivity('policy_published', "Policy {$policy->policy_number} published", [
            'policy_id' => $policy->id,
            'version_id' => $version->id,
            'effective_date' => $effectiveDate,
            'method' => $method,
        ]);

        return $policy->fresh();
    }

    public function deprecate(Policy $policy, string $reason): Policy
    {
        $this->assertCanTransition($policy, 'deprecated');

        $policy->update([
            'status' => 'deprecated',
            'is_active' => false,
            'updated_by' => Auth::id(),
        ]);

        $this->logActivity('policy_deprecated', "Policy {$policy->policy_number} deprecated", [
            'policy_id' => $policy->id,
            'reason' => $reason,
        ]);

        return $policy->fresh();
    }

    public function archive(Policy $policy): Policy
    {
        $this->assertCanTransition($policy, 'archived');

        $policy->update([
            'status' => 'archived',
            'is_active' => false,
            'updated_by' => Auth::id(),
        ]);

        $this->logActivity('policy_archived', "Policy {$policy->policy_number} archived", [
            'policy_id' => $policy->id,
        ]);

        return $policy->fresh();
    }

    public function reactivate(Policy $policy): Policy
    {
        $this->assertCanTransition($policy, 'draft');

        $policy->update([
            'status' => 'draft',
            'is_active' => true,
            'updated_by' => Auth::id(),
        ]);

        $this->logActivity('policy_reactivated', "Policy {$policy->policy_number} reactivated", [
            'policy_id' => $policy->id,
        ]);

        return $policy->fresh();
    }

    public function expire(Policy $policy): Policy
    {
        $this->assertCanTransition($policy, 'expired');

        $policy->update([
            'status' => 'expired',
            'is_active' => false,
            'updated_by' => Auth::id(),
        ]);

        $this->logActivity('policy_expired', "Policy {$policy->policy_number} expired", [
            'policy_id' => $policy->id,
        ]);

        return $policy->fresh();
    }

    private function assertCanTransition(Policy $policy, string $targetStatus): void
    {
        if (!$policy->canTransitionTo($targetStatus)) {
            throw new \RuntimeException(
                "Cannot transition policy from '{$policy->status}' to '{$targetStatus}'."
            );
        }
    }

    private function logActivity(string $action, string $description, ?array $details = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }
}
