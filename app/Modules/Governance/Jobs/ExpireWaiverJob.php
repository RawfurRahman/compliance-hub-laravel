<?php

namespace App\Modules\Governance\Jobs;

use App\Modules\Governance\Events\WaiverExpired;
use App\Modules\Governance\Models\PolicyWaiver;
use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireWaiverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PolicyWaiver $waiver,
    ) {}

    public function handle(): void
    {
        if ($this->waiver->status !== 'approved') {
            return;
        }

        $this->waiver->update(['status' => 'expired']);

        WaiverExpired::dispatch($this->waiver, $this->waiver->policy);

        ActivityLog::create([
            'user_id' => $this->waiver->requested_by,
            'action' => 'waiver_expired',
            'description' => "Waiver #{$this->waiver->id} expired automatically.",
            'details' => [
                'waiver_id' => $this->waiver->id,
                'policy_id' => $this->waiver->policy_id,
                'expires_at' => $this->waiver->expires_at?->toDateString(),
            ],
            'ip_address' => request()->ip(),
        ]);
    }
}
