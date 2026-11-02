<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Baseline browser protections on every web response:
 *  - no framing by other sites (clickjacking on the admin and profile forms),
 *  - no MIME sniffing (uploaded profile photos are served from the web root),
 *  - only the origin is sent as referrer to other sites.
 * Headers a response already sets are left alone.
 */
class SecurityHeaders
{
    private const HEADERS = [
        'X-Frame-Options'        => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy'        => 'strict-origin-when-cross-origin',
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        foreach (self::HEADERS as $name => $value) {
            if (!$response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
