<?php

namespace TDSoft\AiTutor\Licensing;

final class CanonicalDocument
{
    // Protocol v1 is deliberately restricted: a flat object, ASCII values, integers,
    // and an ordered list of module names. Not a general-purpose JSON canonicalizer.
    public function encode(array $document): string
    {
        $required = ['license_id', 'installation_id', 'domain', 'modules', 'student_limit',
            'issued_at', 'expires_at', 'refresh_after', 'grace_until'];
        if (array_diff($required, array_keys($document)) !== []
            || array_diff(array_keys($document), [...$required, 'status']) !== []) {
            throw new LicenseException('LICENSE_INVALID');
        }
        foreach (['license_id', 'installation_id'] as $key) {
            if (! is_string($document[$key]) || ! preg_match('/^[A-Za-z0-9_.-]{1,191}$/D', $document[$key])) {
                throw new LicenseException('LICENSE_INVALID');
            }
        }
        if (! is_string($document['domain']) || ! preg_match('/^[a-z0-9](?:[a-z0-9.-]{0,251}[a-z0-9])?$/D', $document['domain'])
            || str_contains($document['domain'], '..')) {
            throw new LicenseException('LICENSE_INVALID');
        }
        if (! is_array($document['modules']) || ! array_is_list($document['modules'])
            || count($document['modules']) > 64 || count(array_unique($document['modules'], SORT_REGULAR)) !== count($document['modules'])) {
            throw new LicenseException('LICENSE_INVALID');
        }
        foreach ($document['modules'] as $module) {
            if (! is_string($module) || ! preg_match('/^ai_tutor_[a-z0-9_]{1,64}$/D', $module)) {
                throw new LicenseException('LICENSE_INVALID');
            }
        }
        if (! is_int($document['student_limit']) || $document['student_limit'] < 0 || $document['student_limit'] > 2147483647
            || (isset($document['status']) && ! in_array($document['status'], ['active', 'revoked'], true))) {
            throw new LicenseException('LICENSE_INVALID');
        }
        foreach (['issued_at', 'expires_at', 'refresh_after', 'grace_until'] as $key) {
            $this->timestamp($document[$key]);
        }
        if ($document['issued_at'] > $document['refresh_after']
            || $document['issued_at'] >= $document['expires_at']
            || $document['expires_at'] > $document['grace_until']) {
            throw new LicenseException('LICENSE_INVALID');
        }
        ksort($document, SORT_STRING);

        return json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function timestamp(mixed $value): \DateTimeImmutable
    {
        if (! is_string($value)) {
            throw new LicenseException('LICENSE_INVALID');
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new \DateTimeZone('UTC'));
        if (! $date || $date->format('Y-m-d\TH:i:s\Z') !== $value) {
            throw new LicenseException('LICENSE_INVALID');
        }

        return $date;
    }
}
