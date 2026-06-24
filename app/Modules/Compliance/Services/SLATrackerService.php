<?php

namespace App\Modules\Compliance\Services;

use App\Modules\Compliance\Events\SLABreachDetected;
use App\Modules\Compliance\Models\SLATracker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SLATrackerService
{
    public function createFor(Model $trackable, string $slaType, \DateTimeInterface $deadline): SLATracker
    {
        return SLATracker::create([
            'trackable_type' => get_class($trackable),
            'trackable_id' => $trackable->id,
            'sla_type' => $slaType,
            'deadline_at' => $deadline,
            'status' => 'active',
        ]);
    }

    public function checkBreaches(): Collection
    {
        $breached = SLATracker::overdue()->with('trackable')->get();

        foreach ($breached as $sla) {
            $sla->update([
                'status' => 'breached',
                'breached_at' => now(),
            ]);

            event(new SLABreachDetected($sla, $sla->trackable));
        }

        return $breached;
    }

    public function getStats(?int $projectId = null): array
    {
        $query = SLATracker::query();

        if ($projectId) {
            $query->whereHasMorph('trackable', '*', function ($q) use ($projectId) {
                $q->whereHas('riskRegister', fn ($q2) => $q2->where('project_id', $projectId));
            });
        }

        $total = (clone $query)->count();
        $active = (clone $query)->active()->count();
        $breached = (clone $query)->breached()->count();

        return [
            'total' => $total,
            'active' => $active,
            'breached' => $breached,
            'compliance_pct' => $total > 0 ? round(($total - $breached) / $total * 100, 1) : 100,
        ];
    }

    public function resolve(SLATracker $sla): SLATracker
    {
        $sla->update(['status' => 'met']);
        return $sla;
    }
}
