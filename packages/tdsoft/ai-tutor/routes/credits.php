<?php

use Illuminate\Support\Facades\Route;
use TDSoft\AiTutor\Http\CreditAdminController;
use TDSoft\AiTutor\Http\HandleAiErrors;
use TDSoft\AiTutor\Http\RequireCreditAdministrator;

// Controller AND service authorize via CreditAdministrator. Never exempt CSRF.
Route::middleware(['web', 'auth', RequireCreditAdministrator::class, HandleAiErrors::class, 'throttle:30,1'])
    ->prefix('admin/ai/credits')->name('ai-tutor.credits.')->group(function () {
        Route::get('/', [CreditAdminController::class, 'index'])->name('index');
        Route::post('/rules', [CreditAdminController::class, 'rule'])->name('rule');
        Route::post('/grant', [CreditAdminController::class, 'grant'])->name('grant');
    });
