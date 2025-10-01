<?php

namespace App\Http\Controllers;

use App\Support\Health\HealthReporter;
use Illuminate\Http\JsonResponse;

class HealthcheckController
{
    public function __construct(private readonly HealthReporter $reporter)
    {
    }

    public function __invoke(): JsonResponse
    {
        return response()->json($this->reporter->report());
    }
}
