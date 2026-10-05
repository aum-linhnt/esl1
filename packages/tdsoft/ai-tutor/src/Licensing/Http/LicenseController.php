<?php

namespace TDSoft\AiTutor\Licensing\Http;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Contracts\LicenseAdministrator;
use TDSoft\AiTutor\Licensing\InstallationIdentity;
use TDSoft\AiTutor\Licensing\LicenseClient;
use TDSoft\AiTutor\Licensing\LicenseException;
use TDSoft\AiTutor\Licensing\LicenseMode;
use TDSoft\AiTutor\Licensing\LicenseReader;

final class LicenseController extends Controller
{
    public function index(LicenseReader $reader, InstallationIdentity $identity): mixed
    {
        $mode = new LicenseMode;
        try {
            $value = $mode->value();
            if ($value === 'source_owned') {
                return response()->view('ai-tutor::license-mode', ['modeError' => null, 'modules' => $mode->modules()])
                    ->header('Cache-Control', 'no-store, private');
            }
        } catch (LicenseException $error) {
            return response()->view('ai-tutor::license-mode', ['modeError' => $error->errorCode, 'modules' => []], 503)
                ->header('Cache-Control', 'no-store, private');
        }
        $state = $identity->initialized();
        $status = $reader->status();
        $attempts = Schema::hasTable('tutor_ai_license_refresh_attempts')
            ? DB::table('tutor_ai_license_refresh_attempts')->orderByDesc('id')->limit(10)->get()
            : collect();
        $domain = null;
        try {
            $domain = $identity->domain();
        } catch (LicenseException) {
        }

        return response()->view('ai-tutor::license', [
            'license' => $status, 'installationId' => $state?->installation_id, 'domain' => $domain,
            'lastRefreshedAt' => $state?->last_refreshed_at, 'nextAttemptAt' => $state?->next_attempt_at,
            'lastError' => $state?->error_code, 'attempts' => $attempts,
            'configured' => (bool) config('ai-tutor.license.server_url'),
            'migrationReady' => Schema::hasTable('tutor_ai_license_refresh_attempts') && Schema::hasTable('tutor_ai_license_state'),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function initialize(InstallationIdentity $identity): mixed
    {
        return $this->action(fn () => $identity->initialize());
    }

    public function activate(Request $request, LicenseClient $client, LicenseAdministrator $administrator): mixed
    {
        // Avoid validation redirect flashing the submitted license key into session old input.
        $key = $request->input('license_key');
        if (! is_string($key) || trim($key) === '' || strlen($key) > 512) {
            return redirect()->route('ai-tutor.license.index')->with('license_error', 'LICENSE_KEY_REQUIRED');
        }

        return $this->action(fn () => $client->activate(trim($key), $administrator->actorId()));
    }

    public function refresh(LicenseClient $client, LicenseAdministrator $administrator): mixed
    {
        // Explicit admin action can refresh synchronously; scheduled refresh is queued.
        return $this->action(fn () => $client->refresh(true, $administrator->actorId()));
    }

    private function action(callable $action): mixed
    {
        try {
            (new LicenseMode)->requireServer();
            $action();

            return redirect()->route('ai-tutor.license.index')->with('license_notice', 'Đã cập nhật trạng thái license.');
        } catch (LicenseException $error) {
            return redirect()->route('ai-tutor.license.index')->with('license_error', $error->errorCode);
        }
    }
}
