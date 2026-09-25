<?php

namespace TDSoft\AiTutor\Licensing;

class PublicKeyRing
{
    public function key(string $keyId): string
    {
        $paths = config('ai-tutor.license.public_keys', []);
        $path = $paths[$keyId] ?? null;
        // key_id never becomes a filename or URL. Only explicitly configured files.
        if (! is_string($path) || ! str_starts_with($path, '/') || ! is_file($path) || ! is_readable($path) || filesize($path) > 256) {
            throw new LicenseException('LICENSE_PUBLIC_KEY_NOT_CONFIGURED');
        }
        $value = @file_get_contents($path);
        $key = is_string($value) ? base64_decode(trim($value), true) : false;
        if (! is_string($key) || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new LicenseException('LICENSE_PUBLIC_KEY_NOT_CONFIGURED');
        }

        return $key;
    }
}
