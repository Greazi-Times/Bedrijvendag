<?php

namespace App\Casts;

use App\Support\Privacy\EmailCrypto;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * An array (form answers) stored encrypted: submitted answers contain email addresses and other personal data.
 * Plaintext JSON written before encryption was introduced is still readable until it is backfilled.
 */
class EncryptedPayload implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $json = EmailCrypto::tryDecrypt($value);
        $decoded = json_decode((string) $json, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : EmailCrypto::encrypt(json_encode($value, JSON_THROW_ON_ERROR));
    }
}
