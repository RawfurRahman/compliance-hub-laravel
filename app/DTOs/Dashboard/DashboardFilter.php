<?php

namespace App\DTOs\Dashboard;

use Illuminate\Http\Request;

final class DashboardFilter
{
    public function __construct(
        public readonly ?string $businessUnit,
        public readonly ?string $framework,
        public readonly ?string $owner,
        public readonly ?string $category,
        public readonly ?string $vendor,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo,
        public readonly ?string $riskStatus,
        public readonly ?int $projectId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            businessUnit: $request->input('business_unit'),
            framework: $request->input('framework'),
            owner: $request->input('owner'),
            category: $request->input('category'),
            vendor: $request->input('vendor'),
            dateFrom: $request->input('date_from'),
            dateTo: $request->input('date_to'),
            riskStatus: $request->input('risk_status'),
            projectId: $request->integer('project_id') ?: null,
        );
    }

    public function toLegacy(): array
    {
        $legacy = [
            'department' => $this->businessUnit,
            'framework' => $this->framework,
            'owner' => $this->owner,
        ];
        if ($this->riskStatus && $this->riskStatus !== 'All Risk Types') {
            $legacy['risk_type'] = $this->riskStatus;
        }
        return $legacy;
    }

    public function cacheKey(): string
    {
        return md5(json_encode($this->toArray()));
    }

    public function toArray(): array
    {
        return [
            'business_unit' => $this->businessUnit,
            'framework' => $this->framework,
            'owner' => $this->owner,
            'category' => $this->category,
            'vendor' => $this->vendor,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'risk_status' => $this->riskStatus,
            'project_id' => $this->projectId,
        ];
    }
}
