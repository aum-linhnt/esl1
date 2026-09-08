<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AI\GeminiApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class SettingController extends Controller
{
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

        return view('admin.settings.index', compact(
            'geminiStatus', 'isConfigured', 'maskedKey', 'dbDriver', 'systemSettings'
        ));
    }

    /**
     * Save the Gemini API Key & Model to .env file.
     */
    public function updateApiKey(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string|min:10|max:255',
            'model_flash' => 'nullable|string|max:100',
            'model_pro' => 'nullable|string|max:100',
        ]);

        $newKey = trim($request->input('api_key'));
        $modelFlash = trim($request->input('model_flash', 'gemini-1.5-flash'));
        $modelPro = trim($request->input('model_pro', 'gemini-1.5-pro'));

        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        // Update or append GEMINI_API_KEY
        if (str_contains($envContent, 'GEMINI_API_KEY=')) {
            $envContent = preg_replace('/^GEMINI_API_KEY=.*$/m', "GEMINI_API_KEY={$newKey}", $envContent);
        } else {
            $envContent .= "\nGEMINI_API_KEY={$newKey}\n";
        }

        // Update or append GEMINI_MODEL_FLASH
        if (str_contains($envContent, 'GEMINI_MODEL_FLASH=')) {
            $envContent = preg_replace('/^GEMINI_MODEL_FLASH=.*$/m', "GEMINI_MODEL_FLASH={$modelFlash}", $envContent);
        } else {
            $envContent .= "GEMINI_MODEL_FLASH={$modelFlash}\n";
        }

        // Update or append GEMINI_MODEL_PRO
        if (str_contains($envContent, 'GEMINI_MODEL_PRO=')) {
            $envContent = preg_replace('/^GEMINI_MODEL_PRO=.*$/m', "GEMINI_MODEL_PRO={$modelPro}", $envContent);
        } else {
            $envContent .= "GEMINI_MODEL_PRO={$modelPro}\n";
        }

        file_put_contents($envPath, $envContent);

        // Clear config cache so the new key takes effect
        Artisan::call('config:clear');

        return redirect()->route('admin.settings.index')
            ->with('success', 'API Key đã được lưu thành công! Hệ thống AI sẽ sử dụng Gemini API thật.');
    }

    /**
     * AJAX endpoint to test Gemini API connection.
     */
    public function testConnection(): JsonResponse
    {
        // Re-read config after potential .env change
        Artisan::call('config:clear');

        $geminiService = new GeminiApiService();
        $result = $geminiService->testConnection();

        return response()->json($result);
    }

    public function clearCache(Request $request)
    {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');

        return redirect()->route('admin.settings.index')
            ->with('success', 'Đã dọn dẹp bộ nhớ đệm (Cache, Route, Config & View) thành công!');
    }
}
