<?php

namespace App\Support\Privacy;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

/**
 * Protects stored email addresses (and the private answers that contain them) against a database-only breach.
 *
 * - Encryption: Laravel's own Encrypter (AES-256-GCM, authenticated) with a dedicated key, not APP_KEY.
 * - Exact lookup: HMAC-SHA256 of the normalised address with a separate lookup key. Never a plain hash.
 * - Normalisation: trim + Unicode lower-case. Dots, plus-tags and domains are NOT rewritten, so two addresses are
 *   only "the same" when they really are the same mailbox string.
 *
 * Keys come from the environment (config/privacy.php). Production refuses to run without them; local and testing
 * derive them from APP_KEY so development needs no setup.
 */
class EmailCrypto
{
    private static ?Encrypter $encrypter = null;

    private static ?string $lookupKey = null;

    /** @var list<string> */
    private static array $previousLookupKeys = [];

    public static function normalize(?string $email): string
    {
        return mb_strtolower(trim((string) $email));
    }

    public static function encrypt(string $value): string
    {
        return self::encrypter()->encryptString($value);
    }

    /** @throws DecryptException when the value is not valid ciphertext for any known key */
    public static function decrypt(string $payload): string
    {
        return self::encrypter()->decryptString($payload);
    }

    /** Ciphertext decrypts; anything else is a legacy plaintext value that has not been backfilled yet. */
    public static function tryDecrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return self::decrypt($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    public static function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        try {
            self::decrypt($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }

    /** Lookup value stored in the *_hash columns. */
    public static function hash(?string $email): ?string
    {
        $normalized = self::normalize($email);

        return $normalized === '' ? null : hash_hmac('sha256', $normalized, self::lookupKey());
    }

    /** Stable keyed hash for the erasure ledger (separate key, not rotated with the lookup key). */
    public static function erasureHash(?string $email): ?string
    {
        $normalized = self::normalize($email);

        return $normalized === '' ? null : hash_hmac('sha256', $normalized, self::keyBytes(config('privacy.email.erasure_key'), 'email-erasure', 'PRIVACY_ERASURE_KEY'));
    }

    /** Current hash first, then hashes under previous keys, so lookups keep working during a key rotation. */
    public static function lookupHashes(?string $email): array
    {
        $normalized = self::normalize($email);

        if ($normalized === '') {
            return [];
        }

        self::lookupKey();

        return array_values(array_unique(array_map(fn (string $key): string => hash_hmac('sha256', $normalized, $key), [self::$lookupKey, ...self::$previousLookupKeys])));
    }

    /** Forget cached keys (tests, key rotation commands). */
    public static function flush(): void
    {
        self::$encrypter = null;
        self::$lookupKey = null;
        self::$previousLookupKeys = [];
    }

    /** @return array{encryption: bool, lookup: bool} whether each key comes from the environment rather than APP_KEY */
    public static function usesDedicatedKeys(): array
    {
        return ['encryption' => filled(config('privacy.email.encryption_key')), 'lookup' => filled(config('privacy.email.lookup_key'))];
    }

    private static function encrypter(): Encrypter
    {
        if (self::$encrypter) {
            return self::$encrypter;
        }

        $cipher = config('privacy.email.cipher', 'aes-256-gcm');
        $encrypter = new Encrypter(self::keyBytes(config('privacy.email.encryption_key'), 'email-encryption', 'EMAIL_ENCRYPTION_KEY'), $cipher);
        $previous = array_map(fn (string $key): string => self::decodeKey($key), self::list(config('privacy.email.previous_encryption_keys')));
        $previous && $encrypter->previousKeys($previous);

        return self::$encrypter = $encrypter;
    }

    private static function lookupKey(): string
    {
        if (self::$lookupKey === null) {
            self::$lookupKey = self::keyBytes(config('privacy.email.lookup_key'), 'email-lookup', 'EMAIL_LOOKUP_KEY');
            self::$previousLookupKeys = array_map(fn (string $key): string => self::decodeKey($key), self::list(config('privacy.email.previous_lookup_keys')));
        }

        return self::$lookupKey;
    }

    private static function keyBytes(?string $configured, string $context, string $env): string
    {
        if (filled($configured)) {
            return self::decodeKey($configured);
        }

        if (app()->environment('production')) {
            throw new RuntimeException("{$env} is not set. Generate keys with `php artisan privacy:generate-keys` and add them to the environment.");
        }

        // Development and tests only: a purpose-bound key derived from APP_KEY, never APP_KEY itself.
        return hash_hkdf('sha256', self::decodeKey((string) config('app.key')), 32, $context);
    }

    private static function decodeKey(string $key): string
    {
        $key = trim($key);

        return str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) ?: throw new RuntimeException('Invalid base64 key.') : $key;
    }

    /** @return list<string> */
    private static function list(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }
}
