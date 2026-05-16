<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Avoid overriding headers if the server (Apache/cPanel) already sets them.
        $headers = $response->headers;

        if (!$headers->has('X-Content-Type-Options')) {
            $headers->set('X-Content-Type-Options', 'nosniff');
        }

        if (!$headers->has('X-Frame-Options')) {
            // Prefer CSP frame-ancestors, but keep this for broad legacy support.
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        if (!$headers->has('Referrer-Policy')) {
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        if (!$headers->has('Permissions-Policy')) {
            // Conservative baseline; expand only if you actually use these APIs.
            $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        }

        if (!$headers->has('Content-Security-Policy')) {
            // Baseline CSP compatible with typical Laravel + CDN assets.
            // Tighten further once you self-host JS/CSS/fonts.
            $csp = [
                "default-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
                "object-src 'none'",
                "img-src 'self' data: https:",
                "font-src 'self' data: https:",
                "style-src 'self' 'unsafe-inline' https:",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:",
                "connect-src 'self' https:",
                // 'upgrade-insecure-requests', // disabled — no SSL on this server
            ];

            $headers->set('Content-Security-Policy', implode('; ', $csp));
        }

        // Only send HSTS when the request is HTTPS. Ensure you are fully HTTPS before relying on it.
        if ($request->isSecure() && !$headers->has('Strict-Transport-Security')) {
            $headers->set('Strict-Transport-Security', 'max-age=15552000; includeSubDomains');
        }

        return $response;
    }
}
