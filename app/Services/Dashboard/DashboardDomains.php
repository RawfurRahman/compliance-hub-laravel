<?php

namespace App\Services\Dashboard;

final class DashboardDomains
{
    public const KPI = 'kpi';

    public const HEATMAP = 'heatmap';

    public const RISK_RANKING = 'risk_ranking';

    public const COMPLIANCE_SCORECARD = 'compliance_scorecard';

    public const THIRD_PARTY_RISK = 'third_party_risk';

    public const REMEDIATION_TREND = 'remediation_trend';

    public const ALL = [
        self::KPI,
        self::HEATMAP,
        self::RISK_RANKING,
        self::COMPLIANCE_SCORECARD,
        self::THIRD_PARTY_RISK,
        self::REMEDIATION_TREND,
    ];
}
