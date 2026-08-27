<?php

/**
 * Custom router for the PHP built-in development server.
 *
 * Serves static assets from public/ with security headers (the built-in
 * server otherwise bypasses Laravel's middleware for static files) and
 * routes everything else through the framework front controller.
 */
$publicPath = __DIR__.'/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($uri !== '/' && $uri !== '') {
    $realPublic = realpath($publicPath);
    $candidate = realpath($publicPath.$uri);

    if ($candidate !== false
        && is_file($candidate)
        && str_starts_with($candidate, $realPublic.DIRECTORY_SEPARATOR)) {
        $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
        $mime = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'mjs' => 'application/javascript',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'txt' => 'text/plain',
            'map' => 'application/json',
            'json' => 'application/json',
        ][$ext] ?? mime_content_type($candidate) ?: 'application/octet-stream';

        header('Content-Type: '.$mime);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Permitted-Cross-Domain-Policies: none');
        header('Cache-Control: public, max-age=86400');
        readfile($candidate);

        return;
    }
}

require $publicPath.'/index.php';
