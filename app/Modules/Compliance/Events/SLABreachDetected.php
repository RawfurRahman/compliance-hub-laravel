<?php

namespace App\Modules\Compliance\Events;

use App\Modules\Compliance\Models\SLATracker;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SLABreachDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SLATracker $sla,
        public ?Model $trackable,
    ) {}
}
