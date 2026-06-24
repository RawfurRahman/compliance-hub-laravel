<?php

namespace App\Modules\Governance\Console\Commands;

use App\Modules\Governance\Jobs\ExpireWaiverJob;
use App\Modules\Governance\Models\PolicyWaiver;
use Illuminate\Console\Command;

class ExpireOverdueWaivers extends Command
{
    protected $signature = 'governance:expire-waivers';
    protected $description = 'Expire waivers that have passed their expiry date';

    public function handle(): int
    {
        $expiredWaivers = PolicyWaiver::with('policy')
            ->where('status', 'approved')
            ->where('expires_at', '<', now())
            ->get();

        if ($expiredWaivers->isEmpty()) {
            $this->info('No expired waivers found.');
            return Command::SUCCESS;
        }

        foreach ($expiredWaivers as $waiver) {
            ExpireWaiverJob::dispatch($waiver);
        }

        $this->info("Dispatched {$expiredWaivers->count()} waiver expiration job(s).");
        return Command::SUCCESS;
    }
}
