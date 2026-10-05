<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Database-backed, cached application settings.
 *
 * Values saved here override the matching config() keys at boot (see applyToConfig),
 * so existing consumers — GeminiApiService, layouts, theme switcher — keep reading
 * config('services.gemini.*') / config('theme.*') unchanged. `.env` only supplies defaults
 * and is never written at runtime.
 */
class SettingService
{
    public const CACHE_KEY = 'app_settings.v1';

    /**
     * Setting key => [config key, type]. Only keys listed here are applied to config.
     * Types: string | bool | secret (stored encrypted at rest).
     */
    public const CONFIG_MAP = [
        'gemini.api_key'     => ['services.gemini.api_key', 'secret'],
        'gemini.model_flash' => ['services.gemini.model_flash', 'string'],
        'gemini.model_pro'   => ['services.gemini.model_pro', 'string'],
        'theme.mode'         => ['theme.mode', 'string'],
        'theme.accent'       => ['theme.accent', 'string'],
        'theme.glow'         => ['theme.glow', 'bool'],
    ];

    /** Decrypted key => value map, loaded once per request. */
    protected ?array $loaded = null;

    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $rows = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()
            ->get(['key', 'value', 'is_encrypted'])
            ->map(fn (Setting $s) => ['key' => $s->key, 'value' => $s->value, 'is_encrypted' => $s->is_encrypted])
            ->all());

        $values = [];
        foreach ($rows as $row) {
            $values[$row['key']] = $row['is_encrypted'] ? $this->decrypt($row['value']) : $row['value'];
        }

        return $this->loaded = $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        return (self::CONFIG_MAP[$key][1] ?? null) === 'bool' ? $value === '1' : $value;
    }

    public function set(string $key, mixed $value, ?int $userId = null): void
    {
        $this->setMany([$key => $value], $userId);
    }

    /**
     * Persist several settings, then refresh cache and the live config for this request.
     */
    public function setMany(array $values, ?int $userId = null): void
    {
        foreach ($values as $key => $value) {
            $type = self::CONFIG_MAP[$key][1] ?? 'string';

            $stored = match (true) {
                $value === null => null,
                $type === 'bool' => $value ? '1' : '0',
                $type === 'secret' => Crypt::encryptString((string) $value),
                default => (string) $value,
            };

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $stored, 'is_encrypted' => $type === 'secret', 'updated_by' => $userId]
            );
        }

        $this->flush();
        $this->applyToConfig();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }

    /**
     * Overlay stored settings onto runtime config. Unset settings keep their .env/config default.
     */
    public function applyToConfig(): void
    {
        $overrides = [];

        foreach (self::CONFIG_MAP as $key => [$configKey, $type]) {
            $value = $this->get($key);
            if ($value !== null && $value !== '') {
                $overrides[$configKey] = $value;
            }
        }

        if ($overrides) {
            config($overrides);
        }
    }

    protected function decrypt(?string $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        try {
            return Crypt::decryptString($payload);
        } catch (DecryptException) {
            // APP_KEY was rotated: treat as unset so the .env default applies.
            return null;
        }
    }
}
