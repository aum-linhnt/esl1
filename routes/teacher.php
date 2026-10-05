<?php

use App\Http\Controllers\Teacher\AiGeneratorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:teacher,admin'])->group(function () {
    Route::get('/teacher/ai-generator', [AiGeneratorController::class, 'index'])->name('teacher.ai_generator.index');
    Route::post('/teacher/ai-generator/generate', [AiGeneratorController::class, 'generate'])->name('teacher.ai_generator.generate');
    Route::post('/teacher/ai-generator/save', [AiGeneratorController::class, 'save'])->name('teacher.ai_generator.save');
    Route::post('/teacher/ai-generator/save-exam', [AiGeneratorController::class, 'saveExamSet'])->name('teacher.ai_generator.save_exam');
});
