<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonitoringToken;
use App\Support\Health\HealthReporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HealthStatusController extends Controller
{
    public function __construct(private readonly HealthReporter $reporter)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->header('X-Monitoring-Token');

        if (! $this->isRequestIpAllowed($request->ip())) {
            return response()->json(['message' => 'IP not allowed'], 403);
        }

        if (! $token || ! $this->isValidToken($token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $this->reporter->report();
        Log::channel('structured')->info('health.check', [
            'status' => $payload,
            'ip' => $request->ip(),
            'path' => $request->path(),
        ]);

        return response()->json($payload);
    }

    private function isValidToken(string $token): bool
    {
        $hashed = hash('sha256', $token);
        $record = MonitoringToken::query()->where('token_hash', $hashed)->first();

        if (! $record) {
            return false;
        }

        $record->markUsed();

        return true;
    }

    private function isRequestIpAllowed(?string $ip): bool
    {
        $allowed = array_filter(config('monitoring.allowed_ips') ?? []);

        if ($allowed === []) {
            return true;
        }

        return in_array($ip, $allowed, true);
    }
}
