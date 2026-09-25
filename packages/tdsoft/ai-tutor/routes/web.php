<?php

use Illuminate\Support\Facades\Route;
use TDSoft\AiTutor\Licensing\Http\LicenseController;
use TDSoft\AiTutor\Licensing\Http\RequireLicenseAdministrator;

Route::middleware([...config('ai-tutor.license.admin_middleware', ['web', 'auth']), RequireLicenseAdministrator::class])
    ->prefix('admin/ai/license')->name('ai-tutor.license.')->group(function () {
        Route::get('/', [LicenseController::class, 'index'])->name('index');
        Route::post('/initialize', [LicenseController::class, 'initialize'])->middleware('throttle:6,1')->name('initialize');
        Route::post('/activate', [LicenseController::class, 'activate'])->middleware('throttle:6,1')->name('activate');
        Route::post('/refresh', [LicenseController::class, 'refresh'])->middleware('throttle:6,1')->name('refresh');
    });
