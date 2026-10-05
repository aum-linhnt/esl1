<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AI\GeminiApiService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class SettingController extends Controller
{
    public function __construct(private SettingService $settings) {}

    public function index()
    {
        $geminiService = app(GeminiApiService::class);
        $isConfigured = $geminiService->isConfigured();
        $geminiStatus = $isConfigured ? 'Configured & Active' : 'Chưa cấu hình (Fallback Mode)';
        $dbDriver = config('database.default');

        $apiKey = config('services.gemini.api_key');
        $maskedKey = '';
        if (!empty($apiKey) && strlen($apiKey) > 8) {
            $maskedKey = substr($apiKey, 0, 4) . str_repeat('•', strlen($apiKey) - 8) . substr($apiKey, -4);
        } elseif (!empty($apiKey)) {
            $maskedKey = str_repeat('•', strlen($apiKey));
        }

        $systemSettings = [
            'trial_days' => 3,
            'default_xp' => 100,
            'ai_writing_xp' => 15,
            'ai_speaking_xp' => 10,
            'quiz_pass_rate' => 80,
            'gemini_model_flash' => config('services.gemini.model_flash', 'gemini-1.5-flash'),
            'gemini_model_pro' => config('services.gemini.model_pro', 'gemini-1.5-pro'),
            'gemini_temp_flash' => config('services.gemini.temperature_flash', 0.7),
            'gemini_temp_pro' => config('services.gemini.temperature_pro', 0.3),
            'gemini_max_rpm' => config('services.gemini.max_rpm', 15),
        ];

        $themeConfig = [
            'mode' => config('theme.mode', 'dark'),
            'accent' => config('theme.accent', 'indigo'),
            'glow' => config('theme.glow', true),
            'palettes' => config('theme.palettes', []),
        ];

        return view('admin.settings.index', compact(
            'geminiStatus', 'isConfigured', 'maskedKey', 'dbDriver', 'systemSettings', 'themeConfig'
        ));
    }

    /**
     * Save the Gemini API Key & models (encrypted key, stored in DB settings).
     */
    public function updateApiKey(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string|min:10|max:255',
            'model_flash' => 'nullable|string|max:100',
            'model_pro' => 'nullable|string|max:100',
        ]);

        $values = ['gemini.api_key' => trim($request->input('api_key'))];

        // Only override models when provided; otherwise keep current value / .env default.
        if ($request->filled('model_flash')) {
            $values['gemini.model_flash'] = trim($request->input('model_flash'));
        }
        if ($request->filled('model_pro')) {
            $values['gemini.model_pro'] = trim($request->input('model_pro'));
        }

        $this->settings->setMany($values, $request->user()?->id);

        return redirect()->route('admin.settings.index')
            ->with('success', 'API Key đã được lưu thành công! Hệ thống AI sẽ sử dụng Gemini API thật.');
    }

    /**
     * AJAX endpoint to test Gemini API connection.
     */
    public function testConnection(): JsonResponse
    {
        // Config already reflects DB settings (applied at boot), so a fresh instance picks them up.
        $geminiService = new GeminiApiService();
        $result = $geminiService->testConnection();

        return response()->json($result);
    }

    public function clearCache(Request $request)
    {
        // cache:clear also drops cached settings; they are reloaded from the DB on next access.
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');

        return redirect()->route('admin.settings.index')
            ->with('success', 'Đã dọn dẹp bộ nhớ đệm (Cache, Route, Config & View) thành công!');
    }

    /**
     * Update default platform UI theme, accent palette and glow effect.
     */
    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme_mode' => 'required|in:dark,light,auto',
            'theme_accent' => 'required|in:blue,indigo,purple,emerald,amber,rose,cyan',
            'theme_glow' => 'nullable',
        ]);

        $this->settings->setMany([
            'theme.mode' => $request->input('theme_mode'),
            'theme.accent' => $request->input('theme_accent'),
            'theme.glow' => $request->has('theme_glow'),
        ], $request->user()?->id);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Đã lưu cấu hình giao diện & màu sắc mặc định của hệ thống thành công!');
    }
}

