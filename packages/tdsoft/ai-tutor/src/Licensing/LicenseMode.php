<?php

namespace TDSoft\AiTutor\Licensing;

final class LicenseMode
{
    public function value(): string
    {
        $mode = config('ai-tutor.license.mode', 'server');
        if (! in_array($mode, ['server', 'source_owned'], true)) {
            throw new LicenseException('LICENSE_MODE_INVALID');
        }

        return $mode;
    }

    public function modules(): array
    {
        $modules = config('ai-tutor.license.source_modules', []);
        if (! is_array($modules) || ! array_is_list($modules)) {
            throw new LicenseException('LICENSE_SOURCE_MODULES_INVALID');
        }
        foreach ($modules as $module) {
            if (! is_string($module) || ! preg_match('/^ai_tutor_[a-z0-9_]+$/D', $module)) {
                throw new LicenseException('LICENSE_SOURCE_MODULES_INVALID');
            }
        }

        return array_values(array_unique($modules));
    }

    public function requireServer(): void
    {
        if ($this->value() !== 'server') {
            throw new LicenseException('LICENSE_NOT_REQUIRED');
        }
    }

    public function shouldRefresh(): bool
    {
        return config('ai-tutor.license.mode', 'server') === 'server';
    }
}
