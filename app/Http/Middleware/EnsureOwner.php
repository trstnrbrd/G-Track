<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Owner-only pages (User Management, Capital).
 *
 * Hiding the nav link isn't access control: anyone logged in could still open
 * the URL. This is the server-side check, applied as `owner` in routes/web.php.
 */
class EnsureOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'This page is for the owner only.');

        return $next($request);
    }
}
