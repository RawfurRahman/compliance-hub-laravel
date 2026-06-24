<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flag
    |--------------------------------------------------------------------------
    |
    | Enable or disable the entire RMM module. Set to false to prevent any
    | routes, commands, or services from loading.
    |
    */
    'enabled' => env('RMM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Risk Management Module Thresholds (Workbook Legend)
    |--------------------------------------------------------------------------
    |
    | Declares thresholds for risk rating classifications:
    |   Critical >= 128
    |   High     84 to 127
    |   Medium   54 to 83
    |   Low      <= 53
    |
    */
    'thresholds' => [
        'critical' => env('RMM_THRESHOLD_CRITICAL', 128),
        'high'     => env('RMM_THRESHOLD_HIGH', 84),
        'medium'   => env('RMM_THRESHOLD_MEDIUM', 54),
        'low'      => env('RMM_THRESHOLD_LOW', 53),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scoring Calculations Configuration
    |--------------------------------------------------------------------------
    |
    | Declares formula version tracking and precision settings.
    |
    */
    'formula_version' => env('RMM_FORMULA_VERSION', 'v1'),
    'precision'       => (int) env('RMM_PRECISION', 0), // integer math rounding matching workbook

    /*
    |--------------------------------------------------------------------------
    | Legacy Field Mapping
    |--------------------------------------------------------------------------
    |
    | Maps legacy ISO/PCI assessment fields to canonical risk_register columns.
    | Used by MigrationService to convert old gap-assessment records.
    |
    */
    'legacy_mapping' => [
        'iso' => [
            'source_prefix'       => 'iso_gap_assessment_',
            'default_risk_owner'  => 'IT Security Department',
            'default_category'    => 'Cybersecurity',
            'default_department'  => 'IT Security Department',
            'rating_config'       => [
                'High' => [
                    'threat_level'    => 4,
                    'vuln_level'      => 4,
                    'likelihood'      => 3,
                    'impact'          => 4,
                    'risk_rating'     => 96,
                    'residual_tv'     => 2,
                    'residual_lh'     => 2,
                    'residual_rating' => 4,
                ],
                'Medium' => [
                    'threat_level'    => 3,
                    'vuln_level'      => 3,
                    'likelihood'      => 3,
                    'impact'          => 3,
                    'risk_rating'     => 54,
                    'residual_tv'     => 2,
                    'residual_lh'     => 2,
                    'residual_rating' => 4,
                ],
                'Low' => [
                    'threat_level'    => 2,
                    'vuln_level'      => 2,
                    'likelihood'      => 2,
                    'impact'          => 2,
                    'risk_rating'     => 16,
                    'residual_tv'     => 1,
                    'residual_lh'     => 1,
                    'residual_rating' => 1,
                ],
            ],
            'field_map' => [
                'observation_title'  => 'asset_process_service',
                'impact_risk'        => 'threats',
                'gap_description'    => 'vulnerabilities',
                'risk_rating'        => 'measurement',
                'recommendation'     => 'proposed_control',
                'current_state'      => 'existing_control',
                'status'             => 'implementation_status',
            ],
        ],
        'pci' => [
            'source_prefix'       => 'pci_gap_assessment_',
            'default_risk_owner'  => 'PCI Compliance Team',
            'default_category'    => 'Compliance',
            'default_department'  => 'PCI Compliance Team',
            'default_rating'      => 'Medium',
            'field_map'           => [
                'requirement_text' => 'asset_process_service',
                'comments'         => 'existing_control',
                'status'           => 'implementation_status',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Control Mapping Configuration
    |--------------------------------------------------------------------------
    |
    | Scoring weights and thresholds for the control-matching engine.
    |
    */
    'matching' => [
        'fuzzy_weight'         => (float) env('RMM_FUZZY_WEIGHT', 0.40),
        'keyword_weight'       => (float) env('RMM_KEYWORD_WEIGHT', 0.25),
        'min_confidence'       => (float) env('RMM_MIN_CONFIDENCE', 15.0),
        'suggestion_limit'     => (int) env('RMM_SUGGESTION_LIMIT', 10),
        'presence_bonus'       => (float) env('RMM_PRESENCE_BONUS', 20.0),
    ],
];
