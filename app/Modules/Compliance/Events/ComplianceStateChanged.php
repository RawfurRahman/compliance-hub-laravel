<?php

namespace App\Modules\Compliance\Events;

use App\Models\AssessmentFinding;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplianceStateChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AssessmentFinding $finding,
        public ?string $oldState,
        public string $newState,
    ) {}
}
