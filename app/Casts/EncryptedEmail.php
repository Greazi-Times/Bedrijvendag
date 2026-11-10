<?php

namespace App\Casts;

use App\Support\Privacy\EmailCrypto;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * An email address stored encrypted. Reading decrypts; a value that is not ciphertext is a legacy plaintext row
 * waiting for `privacy:encrypt-emails` and is returned as is, so the transition needs no downtime. Writing always encrypts.
 */
class EncryptedEmail implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return EmailCrypto::tryDecrypt($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $value = $value === null ? null : trim((string) $value);

        return $value === null || $value === '' ? null : EmailCrypto::encrypt($value);
    }
}
