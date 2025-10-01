<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthcheckController;
use App\Http\Controllers\Portal\KnowledgeBaseController;
use App\Http\Controllers\Portal\PortalTicketController;

Route::redirect('/', '/portal/default');

Route::get('/health', HealthcheckController::class);

Route::prefix('portal/{brand:slug}')
    ->middleware([\App\Http\Middleware\SetBrandFromRoute::class])
    ->group(function (): void {
        Route::get('/', [KnowledgeBaseController::class, 'index'])->name('portal.brand.home');
        Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('portal.knowledge.index');
        Route::get('/knowledge-base/{article:slug}', [KnowledgeBaseController::class, 'show'])->name('portal.knowledge.show');

        Route::get('/tickets/create', [PortalTicketController::class, 'create'])->name('portal.tickets.create');
        Route::post('/tickets', [PortalTicketController::class, 'store'])->name('portal.tickets.store');
        Route::get('/tickets/thanks/{reference}', [PortalTicketController::class, 'thanks'])->name('portal.tickets.thanks');
        Route::get('/tickets/{reference}', [PortalTicketController::class, 'show'])->name('portal.tickets.show');
    });
