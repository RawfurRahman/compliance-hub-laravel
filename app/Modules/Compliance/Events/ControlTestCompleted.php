<?php

namespace App\Modules\Compliance\Events;

use App\Models\AssessmentFinding;
use App\Modules\Compliance\Models\ControlTest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ControlTestCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ControlTest $test,
        public ?AssessmentFinding $finding,
        public string $result,
    ) {}
}
