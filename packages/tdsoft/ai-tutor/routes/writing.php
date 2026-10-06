<?php

use Illuminate\Support\Facades\Route;
use TDSoft\AiTutor\Http\HandleAiErrors;
use TDSoft\AiTutor\Http\RequireWritingSchema;
use TDSoft\AiTutor\Http\WritingController;
use TDSoft\AiTutor\Http\WritingPageController;
use TDSoft\AiTutor\Licensing\Http\RequireModule;

Route::middleware(['web', 'auth', HandleAiErrors::class, RequireModule::class.':ai_tutor_writing', RequireWritingSchema::class])
    ->prefix('ai-tutor/writing')->name('ai-tutor.writing.')->group(function () {
        Route::get('/', [WritingPageController::class, 'index'])->name('index');
        Route::get('/{id}', [WritingPageController::class, 'show'])->name('show');
    });

Route::middleware(['web', 'auth', HandleAiErrors::class, RequireModule::class.':ai_tutor_writing', RequireWritingSchema::class, 'throttle:30,1'])
    ->prefix('ai-tutor/api/v1/writing')->group(function () {
        Route::get('drafts', [WritingController::class, 'index']);
        Route::post('drafts', [WritingController::class, 'store']);
        Route::get('drafts/{id}', [WritingController::class, 'show']);
        Route::patch('drafts/{id}', [WritingController::class, 'update']);
        Route::post('drafts/{id}/submit', [WritingController::class, 'submit']);
        Route::get('drafts/{id}/submissions', [WritingController::class, 'submissions']);
        Route::get('submissions/{id}', [WritingController::class, 'submission']);
        Route::post('submissions/{id}/retry', [WritingController::class, 'retry']);
    });
