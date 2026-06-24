<?php

return [
    'enabled' => env('COMPLIANCE_ENABLED', true),

    'sla_defaults' => [
        'response_hours' => (int) env('COMPLIANCE_SLA_RESPONSE_HOURS', 24),
        'resolution_hours' => (int) env('COMPLIANCE_SLA_RESOLUTION_HOURS', 168),
    ],

    'snapshot_types' => ['weekly', 'monthly', 'quarterly', 'ondemand'],
];
