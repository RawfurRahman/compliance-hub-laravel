<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PCI DSS Module Settings
    |--------------------------------------------------------------------------
    |
    | This section contains settings specific to the PCI DSS module,
    | such as the list of available payment channels.
    |
    */

    'pci_dss' => [
        'payment_channels' => [
            'Mail order / telephone order (MOTO)',
            'E-Commerce',
            'Card-present',
        ],
    ],

];
