<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpeakingPracticeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Speech AI Pronunciation Assessment Endpoints (proxies or direct callers
| to speechai.lmsviet.com).
|
*/

use App\Http\Controllers\FileController;

Route::prefix('v1')->group(function () {
    Route::post('/assess', [SpeakingPracticeController::class, 'assessMultipart'])->name('api.v1.assess');
    Route::post('/assess-base64', [SpeakingPracticeController::class, 'assessBase64'])->name('api.v1.assess.base64');
    Route::post('/assess-url', [SpeakingPracticeController::class, 'assessUrl'])->name('api.v1.assess.url');

    // Centralized File Management Endpoints
    Route::post('/files/upload', [FileController::class, 'upload'])->name('api.v1.files.upload');
    Route::post('/files/upload-temp', [FileController::class, 'uploadTemp'])->name('api.v1.files.upload-temp');
});

