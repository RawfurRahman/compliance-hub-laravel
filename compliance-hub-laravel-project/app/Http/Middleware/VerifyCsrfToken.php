<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // This line tells Laravel to bypass the CSRF check for any URLs
        // that start with 'n8n/'. This will allow our n8n workflows
        // to send data back to the application without being blocked.
        'n8n/*',
    ];
}
