<?php

use Illuminate\Support\Facades\Route;
use TDSoft\AiTutor\Http\HandleAiErrors;
use TDSoft\AiTutor\Http\ReconciliationController;
use TDSoft\AiTutor\Http\RequireCreditAdministrator;

Route::middleware(['web', 'auth', RequireCreditAdministrator::class, HandleAiErrors::class, 'throttle:30,1'])
    ->prefix('admin/ai/reconciliation')->name('ai-tutor.reconciliation.')->group(function () {
        Route::get('/', [ReconciliationController::class, 'index'])->name('index');
        Route::post('/{requestId}', [ReconciliationController::class, 'update'])
            ->whereUuid('requestId')->name('update');
    });
