<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\ThirdPartyVendor;
use Illuminate\Support\Facades\Auth;

class ThirdPartyVendorService
{
    public function listForProject(?int $projectId)
    {
        $query = ThirdPartyVendor::query();
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        return $query->orderBy('vendor_name')->get();
    }

    public function create(array $data): ThirdPartyVendor
    {
        $data['created_by'] = Auth::id();
        return ThirdPartyVendor::create($data);
    }

    public function update(ThirdPartyVendor $vendor, array $data): ThirdPartyVendor
    {
        $vendor->update($data);
        return $vendor->fresh();
    }

    public function delete(ThirdPartyVendor $vendor): void
    {
        $vendor->delete();
    }

    public function assessRiskTier(ThirdPartyVendor $vendor): string
    {
        if ($vendor->criticality === 'critical' && $vendor->data_classification === 'restricted') {
            return 'tier_1';
        }
        if ($vendor->criticality === 'high' || $vendor->data_classification === 'confidential') {
            return 'tier_2';
        }
        return 'tier_3';
    }
}
