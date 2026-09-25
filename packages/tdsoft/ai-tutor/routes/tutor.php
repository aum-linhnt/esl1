<?php

use Illuminate\Support\Facades\Route;
use TDSoft\AiTutor\Http\ConversationController;
use TDSoft\AiTutor\Http\HandleAiErrors;
use TDSoft\AiTutor\Http\KnowledgeController;
use TDSoft\AiTutor\Licensing\Http\RequireModule;
use TDSoft\AiTutor\Http\PageController;

Route::middleware(['web', 'auth', HandleAiErrors::class, RequireModule::class.':ai_tutor_core'])
    ->get('ai-tutor', [PageController::class, 'tutor'])->name('ai-tutor.page');
Route::middleware(['web', 'auth', HandleAiErrors::class, RequireModule::class.':ai_tutor_knowledge'])
    ->get('admin/ai/knowledge', [PageController::class, 'knowledge'])->name('ai-tutor.knowledge');

// Not under /api/*: the host excludes that prefix from CSRF checks.
Route::middleware(['web', 'auth', HandleAiErrors::class, 'throttle:30,1'])
    ->prefix('ai-tutor/api/v1')->group(function () {
        Route::middleware(RequireModule::class.':ai_tutor_core')->group(function () {
            Route::get('context', [PageController::class, 'context']);
            Route::get('conversations', [ConversationController::class, 'index']);
            Route::post('conversations', [ConversationController::class, 'store']);
            Route::get('conversations/{id}', [ConversationController::class, 'show']);
            Route::get('conversations/{id}/export', [ConversationController::class, 'export']);
            Route::delete('conversations/{id}', [ConversationController::class, 'destroy']);
            Route::post('conversations/{id}/messages', [ConversationController::class, 'send']);
            Route::get('messages/{id}/stream', [ConversationController::class, 'stream']);
            Route::get('messages/{id}/sources/{chunk}', [ConversationController::class, 'source']);
            Route::post('messages/{id}/feedback', [ConversationController::class, 'feedback']);
        });
        Route::middleware(RequireModule::class.':ai_tutor_knowledge')->group(function () {
            Route::get('knowledge/sync/courses', [\TDSoft\AiTutor\Http\CourseSyncController::class, 'courses']);
            Route::get('knowledge/sync/preview', [\TDSoft\AiTutor\Http\CourseSyncController::class, 'preview']);
            Route::post('knowledge/sync', [\TDSoft\AiTutor\Http\CourseSyncController::class, 'store']);
            Route::get('knowledge/documents', [KnowledgeController::class, 'index']);
            Route::post('knowledge/documents', [KnowledgeController::class, 'store']);
            Route::post('knowledge/documents/{id}/versions', [KnowledgeController::class, 'version']);
            Route::get('knowledge/documents/{id}/versions', [KnowledgeController::class, 'versions']);
            Route::post('knowledge/documents/{id}/withdraw', [KnowledgeController::class, 'withdraw']);
            Route::get('knowledge/document-versions/{id}/content', [KnowledgeController::class, 'preview']);
            Route::post('knowledge/document-versions/{id}/process', [KnowledgeController::class, 'process']);
            Route::get('knowledge/document-versions/{id}', [KnowledgeController::class, 'status']);
            Route::post('knowledge/document-versions/{id}/publish', [KnowledgeController::class, 'publish']);
        });
    });
