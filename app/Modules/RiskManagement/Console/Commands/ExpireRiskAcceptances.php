<?php

namespace App\Modules\RiskManagement\Console\Commands;

use App\Modules\RiskManagement\Models\RiskAcceptance;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Events\RiskLifecycleChanged;
use Illuminate\Console\Command;

class ExpireRiskAcceptances extends Command
{
    protected $signature = 'risks:expire-acceptances';
    protected $description = 'Expire risk acceptances past their expiry date';

    public function handle(): int
    {
        $expired = RiskAcceptance::where('status', 'Approved')
            ->where('expiry_date', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $acceptance) {
            $acceptance->update(['status' => 'Expired']);

            $risk = $acceptance->risk;
            if ($risk && $risk->lifecycle_status === 'accepted') {
                $oldStatus = $risk->lifecycle_status;
                $risk->update(['lifecycle_status' => 'expired']);

                RiskLifecycleChanged::dispatch($risk, $oldStatus, 'expired', 'Acceptance expired');
            }

            $count++;
        }

        $this->info("Expired {$count} risk acceptance(s).");

        return Command::SUCCESS;
    }
}
