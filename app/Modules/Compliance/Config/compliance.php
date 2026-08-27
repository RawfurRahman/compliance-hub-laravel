<?php

return [
    'enabled' => env('COMPLIANCE_ENABLED', true),

    'snapshot_types' => ['weekly', 'monthly', 'quarterly', 'ondemand'],
];
