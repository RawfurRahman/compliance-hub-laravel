<?php

return [
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ],
    'csp' => [
        'enabled' => true,
        'report_only' => env('CSP_REPORT_ONLY', false),
        'directives' => [
            'default-src' => ["'self'"],
            // Login/dashboard/Alpine.js views rely on inline style="" attributes
            // (animation-delay, gradients) and Alpine :style bindings — no nonce
            // plumbing exists for styles, so 'unsafe-inline' is required here.
            'style-src' => ["'self'", "'unsafe-inline'"],
            // Tailwind's browser build (js/tailwind.min.js) JIT-compiles utility
            // classes at runtime via new Function()/eval, and dashboard.blade.php
            // defines Alpine x-data components (scanHealth()) in an inline
            // <script> block — no nonce plumbing exists, so both are required.
            'script-src' => ["'self'", "'unsafe-eval'", "'unsafe-inline'"],
            // ApexCharts (dashboard) renders chart labels/tooltips as inline SVG
            // and data: URIs for exported images.
            'img-src' => ["'self'", 'data:'],
            'font-src' => ["'self'", 'data:'],
        ],
    ],
];
