<?php

namespace TDSoft\AiTutor\Licensing;

final class SignatureVerifier
{
    public function __construct(private CanonicalDocument $canonical, private PublicKeyRing $keys) {}

    public function verify(array $envelope): array
    {
        if (array_diff(array_keys($envelope), ['document', 'signature', 'key_id']) !== []
            || ! is_array($envelope['document'] ?? null)
            || ! is_string($envelope['signature'] ?? null)
            || ! is_string($envelope['key_id'] ?? null)
            || strlen($envelope['signature']) > 128
            || ! preg_match('/^[a-zA-Z0-9_.-]{1,100}$/D', $envelope['key_id'])) {
            throw new LicenseException('LICENSE_INVALID');
        }
        $document = $envelope['document'];
        $bytes = $this->canonical->encode($document);
        $signature = base64_decode($envelope['signature'], true);
        if (! is_string($signature) || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || ! sodium_crypto_sign_verify_detached($signature, $bytes, $this->keys->key($envelope['key_id']))) {
            throw new LicenseException('LICENSE_INVALID_SIGNATURE');
        }
        // Allow small clock skew only for issuance, never for expiration.
        if ($this->canonical->timestamp($document['issued_at'])->getTimestamp() > now()->timestamp + 300) {
            throw new LicenseException('LICENSE_INVALID');
        }

        return $document;
    }
}
