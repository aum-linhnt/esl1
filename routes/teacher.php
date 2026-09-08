<?php

use App\Http\Controllers\Teacher\AiGeneratorController;
use Illuminate\Support\Facades\Route;

// ─── Teacher & Academic Tools Routes ───
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/teacher/ai-generator', [AiGeneratorController::class, 'index'])->name('teacher.ai_generator.index');
    Route::post('/teacher/ai-generator/generate', [AiGeneratorController::class, 'generate'])->name('teacher.ai_generator.generate');
    Route::post('/teacher/ai-generator/save', [AiGeneratorController::class, 'save'])->name('teacher.ai_generator.save');
});
