<?php

namespace App\Casts;

use App\Support\Privacy\EmailCrypto;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * A short personal text (such as a contact's name) stored with authenticated encryption, using the same dedicated key
 * as email addresses. Not searchable or sortable in SQL by design. A value that is not ciphertext is legacy plaintext
 * waiting for `privacy:encrypt-emails` and is returned as is.
 */
class EncryptedText implements CastsAttributes
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
