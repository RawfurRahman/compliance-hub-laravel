<?php

namespace App\Modules\Compliance\Jobs;

use App\Modules\Compliance\Services\SLATrackerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckSLABreachesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SLATrackerService $service): void
    {
        $service->checkBreaches();
    }
}
