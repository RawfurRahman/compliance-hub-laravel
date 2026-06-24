<?php

namespace App\Modules\Compliance\Events;

use App\Modules\Compliance\Models\ComplianceSnapshot;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplianceSnapshotTaken
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ComplianceSnapshot $snapshot,
    ) {}
}
