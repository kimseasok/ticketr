<?php

use App\Http\Controllers\Api\ChannelIngestionController;
use App\Http\Controllers\Api\HealthStatusController;
use App\Http\Controllers\Api\TicketBulkActionController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketMessageController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthStatusController::class);

Route::middleware(['auth'])->group(function (): void {
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::post('/tickets/bulk-actions', [TicketBulkActionController::class, 'store']);
    Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
    Route::put('/tickets/{ticket}', [TicketController::class, 'update']);
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy']);

    Route::get('/tickets/{ticket}/messages', [TicketMessageController::class, 'index']);
    Route::post('/tickets/{ticket}/messages', [TicketMessageController::class, 'store']);
    Route::post('/tickets/{ticket}/ingest', [ChannelIngestionController::class, 'store']);

    Route::post('/security/two-factor', [TwoFactorController::class, 'enroll']);
    Route::post('/security/two-factor/confirm', [TwoFactorController::class, 'confirm']);
    Route::delete('/security/two-factor', [TwoFactorController::class, 'disable']);
    Route::patch('/security/ip-restrictions', [TwoFactorController::class, 'updateIpRestrictions']);
});
