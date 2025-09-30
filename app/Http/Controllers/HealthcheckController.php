<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Scout\EngineManager;

class HealthcheckController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'scout' => $this->checkScout(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected function checkRedis(): bool
    {
        try {
            $response = Redis::connection()->ping();

            return in_array($response, ['PONG', '+PONG'], true);
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected function checkQueue(): bool
    {
        try {
            $queue = Queue::connection();

            return $queue instanceof QueueContract;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected function checkScout(): bool
    {
        try {
            $engineManager = App::make(EngineManager::class);
            $engineManager->engine();

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
