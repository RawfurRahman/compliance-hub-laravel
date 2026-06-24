<?php

namespace App\Modules\RiskManagement\Listeners;

use App\Modules\RiskManagement\Events\ResidualAppetiteCrossed;
use App\Modules\RiskManagement\Models\RiskNotification;

/**
 * Reacts to a risk's residual score crossing the appetite threshold.
 *
 * On a crossing OUT of appetite (within -> exceeds): notifies the risk owner
 * and escalates the lifecycle to "escalated" when the transition is allowed.
 * On a crossing back INTO appetite: records an informational notification.
 */
class ResidualAppetiteCrossedListener
{
    public function handle(ResidualAppetiteCrossed $event): void
    {
        $risk = $event->risk;
        $movedOutOfAppetite = $event->newStatus === 'exceeds_appetite'
            && $event->previousStatus !== 'exceeds_appetite';

        if ($movedOutOfAppetite) {
            $this->notify(
                $risk,
                'residual_appetite_breach',
                'Residual risk exceeds appetite',
                sprintf(
                    'Risk %s residual score is now %d, which exceeds the risk appetite threshold.',
                    $risk->serial_no,
                    $event->residualScore
                )
            );

            // Escalate the lifecycle if the current state allows it.
            if (method_exists($risk, 'canTransitionTo') && $risk->canTransitionTo('escalated')) {
                $risk->lifecycle_status = 'escalated';
                $risk->saveQuietly();
            }

            return;
        }

        $movedIntoAppetite = $event->newStatus === 'within_appetite'
            && $event->previousStatus === 'exceeds_appetite';

        if ($movedIntoAppetite) {
            $this->notify(
                $risk,
                'residual_appetite_restored',
                'Residual risk back within appetite',
                sprintf(
                    'Risk %s residual score is now %d, back within the risk appetite threshold.',
                    $risk->serial_no,
                    $event->residualScore
                )
            );
        }
    }

    private function notify($risk, string $type, string $title, string $body): void
    {
        $userId = $risk->owner_user_id ?? $risk->updated_by ?? $risk->created_by;
        if (!$userId) {
            return;
        }

        RiskNotification::create([
            'risk_register_id' => $risk->id,
            'user_id'          => $userId,
            'type'             => $type,
            'title'            => $title,
            'body'             => $body,
        ]);
    }
}
