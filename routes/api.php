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
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\MessageApiController;

Route::prefix('v1')->group(function () {
    Route::post('/assess', [SpeakingPracticeController::class, 'assessMultipart'])->name('api.v1.assess');
    Route::post('/assess-base64', [SpeakingPracticeController::class, 'assessBase64'])->name('api.v1.assess.base64');
    Route::post('/assess-url', [SpeakingPracticeController::class, 'assessUrl'])->name('api.v1.assess.url');

    // ─── Authenticated API Endpoints (Requires Bearer Token) ───
    Route::middleware(['auth:sanctum'])->group(function () {
        // Centralized File Management Endpoints
        Route::post('/files/upload', [FileController::class, 'upload'])->name('api.v1.files.upload');
        Route::post('/files/upload-temp', [FileController::class, 'uploadTemp'])->name('api.v1.files.upload-temp');

        // Notifications API
        Route::get('/notifications', [NotificationApiController::class, 'index'])->name('api.v1.notifications.index');
        Route::get('/notifications/unread-count', [NotificationApiController::class, 'unreadCount'])->name('api.v1.notifications.unreadCount');
        Route::post('/notifications/{id}/read', [NotificationApiController::class, 'markAsRead'])->name('api.v1.notifications.markAsRead');
        Route::post('/notifications/mark-all-read', [NotificationApiController::class, 'markAllAsRead'])->name('api.v1.notifications.markAllAsRead');
        Route::delete('/notifications/{id}', [NotificationApiController::class, 'destroy'])->name('api.v1.notifications.destroy');
        Route::delete('/notifications', [NotificationApiController::class, 'clearAll'])->name('api.v1.notifications.clearAll');
        Route::post('/notifications/send', [NotificationApiController::class, 'send'])->name('api.v1.notifications.send');

        // Messages API
        Route::get('/messages/conversations', [MessageApiController::class, 'conversations'])->name('api.v1.messages.conversations');
        Route::get('/messages/unread-count', [MessageApiController::class, 'unreadCount'])->name('api.v1.messages.unreadCount');
        Route::post('/messages/conversations', [MessageApiController::class, 'createConversation'])->name('api.v1.messages.createConversation');
        Route::get('/messages/conversations/{id}', [MessageApiController::class, 'showConversation'])->name('api.v1.messages.showConversation');
        Route::post('/messages/conversations/{id}/messages', [MessageApiController::class, 'sendMessage'])->name('api.v1.messages.sendMessage');
        Route::post('/messages/direct', [MessageApiController::class, 'sendDirect'])->name('api.v1.messages.sendDirect');
        Route::post('/messages/conversations/{id}/read', [MessageApiController::class, 'markAsRead'])->name('api.v1.messages.markAsRead');
        Route::get('/messages/users/search', [MessageApiController::class, 'searchUsers'])->name('api.v1.messages.searchUsers');
    });
});

