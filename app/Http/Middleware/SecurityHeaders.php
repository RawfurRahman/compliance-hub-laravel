<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        \Log::info('SecurityHeaders middleware hit');
        $response = $next($request);

        // Do not process non-standard responses
        if (!property_exists($response, 'headers') && !method_exists($response, 'header')) {
            return $response;
        }

        $headers = config('security.headers', []);
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        if (config('security.csp.enabled', false)) {
            $policy = $this->buildCspString(config('security.csp.directives', []));
            $headerName = config('security.csp.report_only', false) 
                ? 'Content-Security-Policy-Report-Only' 
                : 'Content-Security-Policy';
            
            $response->headers->set($headerName, $policy);
        }

        return $response;
    }

    protected function buildCspString(array $directives): string
    {
        $policy = [];
        foreach ($directives as $directive => $sources) {
            $sources = array_filter($sources, fn($src) => trim($src) !== '*');
            
            if (!empty($sources)) {
                $policy[] = $directive . ' ' . implode(' ', $sources);
            } else {
                $policy[] = $directive;
            }
        }
        
        return implode('; ', $policy);
    }
}
