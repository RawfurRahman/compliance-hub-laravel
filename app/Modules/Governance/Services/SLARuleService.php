<?php

namespace App\Modules\Governance\Services;

use App\Models\ActivityLog;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\SLARule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SLARuleService
{
    public function list(array $filters = []): Collection
    {
        $query = SLARule::with('escalationUser');

        if (!empty($filters['policy_id'])) {
            $query->where('policy_id', $filters['policy_id']);
        }
        if (!empty($filters['trigger_event'])) {
            $query->where('trigger_event', $filters['trigger_event']);
        }
        if (!empty($filters['is_active'])) {
            $query->where('is_active', true);
        }

        return $query->orderBy('name')->get();
    }

    public function create(array $data): SLARule
    {
        $rule = SLARule::create([
            'policy_id' => $data['policy_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'trigger_event' => $data['trigger_event'],
            'action_type' => $data['action_type'],
            'sla_hours' => $data['sla_hours'],
            'escalation_interval_hours' => $data['escalation_interval_hours'] ?? null,
            'escalation_user_id' => $data['escalation_user_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'sla_rule_created',
            'description' => "SLA rule '{$rule->name}' created",
            'details' => [
                'rule_id' => $rule->id,
                'trigger_event' => $rule->trigger_event,
                'sla_hours' => $rule->sla_hours,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $rule->fresh();
    }

    public function update(SLARule $rule, array $data): SLARule
    {
        $rule->update([
            'name' => $data['name'] ?? $rule->name,
            'description' => $data['description'] ?? $rule->description,
            'trigger_event' => $data['trigger_event'] ?? $rule->trigger_event,
            'action_type' => $data['action_type'] ?? $rule->action_type,
            'sla_hours' => $data['sla_hours'] ?? $rule->sla_hours,
            'escalation_interval_hours' => $data['escalation_interval_hours'] ?? $rule->escalation_interval_hours,
            'escalation_user_id' => $data['escalation_user_id'] ?? $rule->escalation_user_id,
            'is_active' => $data['is_active'] ?? $rule->is_active,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'sla_rule_updated',
            'description' => "SLA rule '{$rule->name}' updated",
            'details' => [
                'rule_id' => $rule->id,
                'changes' => $rule->getChanges(),
            ],
            'ip_address' => request()->ip(),
        ]);

        return $rule->fresh();
    }

    public function delete(SLARule $rule): void
    {
        $ruleName = $rule->name;

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'sla_rule_deleted',
            'description' => "SLA rule '{$ruleName}' deleted",
            'details' => ['rule_id' => $rule->id],
            'ip_address' => request()->ip(),
        ]);

        $rule->delete();
    }

    public function checkSLABreaches(): Collection
    {
        return SLARule::with('policy')
            ->where('is_active', true)
            ->get();
    }
}
