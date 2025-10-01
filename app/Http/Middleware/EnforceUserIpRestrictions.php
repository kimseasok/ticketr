<?php

namespace App\Http\Middleware;

use App\Support\Security\SecurityEventLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceUserIpRestrictions
{
    public function __construct(private readonly SecurityEventLogger $logger)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $ip = $request->ip();
        $allowlist = collect($user->ip_allowlist ?? [])->filter()->all();
        $blocklist = collect($user->ip_blocklist ?? [])->filter()->all();

        if ($blocklist !== [] && in_array($ip, $blocklist, true)) {
            $this->logger->log($user, 'security.ip.blocked', ['ip' => $ip]);

            return response()->json(['message' => 'Access blocked from this IP'], 403);
        }

        if ($allowlist !== [] && ! in_array($ip, $allowlist, true)) {
            $this->logger->log($user, 'security.ip.denied', ['ip' => $ip]);

            return response()->json(['message' => 'IP not allowed'], 403);
        }

        return $next($request);
    }
}
