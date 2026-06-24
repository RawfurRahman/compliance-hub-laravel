<?php

namespace App\Modules\Governance\Services;

use App\Models\ActivityLog;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class PolicyVersionService
{
    public function createVersion(Policy $policy, array $data): PolicyVersion
    {
        $versionNumber = ($policy->current_version ?? 0) + 1;

        $version = $policy->versions()->create([
            'version_number' => $versionNumber,
            'title' => $data['title'] ?? $policy->title,
            'content' => $data['content'] ?? '',
            'change_summary' => $data['change_summary'] ?? null,
            'status' => $policy->status,
            'effective_date' => $data['effective_date'] ?? $policy->effective_date,
            'expires_at' => $data['expires_at'] ?? $policy->expires_at,
        ]);

        $policy->update(['current_version' => $versionNumber]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'policy_version_created',
            'description' => "Version {$versionNumber} created for policy {$policy->policy_number}",
            'details' => [
                'policy_id' => $policy->id,
                'version_number' => $versionNumber,
                'change_summary' => $data['change_summary'] ?? null,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $version;
    }

    public function getVersionHistory(Policy $policy): Collection
    {
        return $policy->versions()->orderByDesc('version_number')->get();
    }

    public function getVersion(Policy $policy, int $versionNumber): PolicyVersion
    {
        return $policy->versions()
            ->where('version_number', $versionNumber)
            ->firstOrFail();
    }
}
