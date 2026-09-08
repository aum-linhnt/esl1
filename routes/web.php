<?php

use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

// ─── Public Routes ───
Route::get('/', function () {
    return redirect()->route('login');
});

// Language Switcher (Moodle-style: vi, en)
Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

// Serve public storage files directly (enables video streaming and supports volumes without symlinks)
Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);

    $realPublicPath = realpath(storage_path('app/public'));
    $realFilePath = realpath($fullPath);

    if (!$realFilePath || !str_starts_with($realFilePath, $realPublicPath) || !file_exists($realFilePath)) {
        abort(404);
    }

    return response()->file($realFilePath, [
        'Cache-Control' => 'public, max-age=31536000',
        'Accept-Ranges' => 'bytes',
    ]);
})->where('path', '.*')->name('public.storage');

// Social login mock routes
Route::get('/auth/{provider}/redirect', function ($provider) {
    return redirect()->route('login')->with('info', "Tính năng đăng nhập bằng {$provider} đang được phát triển.");
})->name('social.redirect');

Route::get('/auth/{provider}/callback', function ($provider) {
    return redirect()->route('login');
})->name('social.callback');

// ─── Modular Route Groups ───
require __DIR__.'/student.php';
require __DIR__.'/teacher.php';
require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
