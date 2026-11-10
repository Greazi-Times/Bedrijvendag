<?php

namespace App\Support\Privacy;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Encrypts email columns (and answer payloads) that still hold legacy plaintext, in place and in batches.
 * Each value is encrypted, decrypted again and compared before it is written, so a value that cannot round trip is
 * never replaced. With $rotate, values that are already encrypted are re-encrypted under the current key and the lookup
 * hashes are recomputed (key rotation). Running it twice changes nothing the second time.
 */
class EmailBackfill
{
    /** @var array<string, array{emails: array<string, string|null>, payloads: list<string>}> table => column => hash column */
    public const TARGETS = [
        'enrollments' => ['emails' => ['contact_email' => 'contact_email_hash', 'contact_name' => null], 'payloads' => ['answers']],
        'enrollment_info_submissions' => ['emails' => [], 'payloads' => ['answers']],
        'enrollment_contact_changes' => ['emails' => ['email' => 'email_hash', 'name' => null], 'payloads' => []],
        'borrel_enrollments' => ['emails' => ['email' => 'email_hash', 'name' => null], 'payloads' => []],
        'newsletter_subscribers' => ['emails' => ['email' => 'email_hash'], 'payloads' => []],
        'company_access_requests' => ['emails' => ['contact_email' => null, 'contact_name' => null], 'payloads' => []],
        'company_interest_requests' => ['emails' => ['contact_email' => null, 'contact_name' => null], 'payloads' => []],
        'company_profile_submissions' => ['emails' => ['contact_email' => null, 'contact_name' => null], 'payloads' => []],
        'companies' => ['emails' => ['profile_contact_email' => null], 'payloads' => []],
    ];

    /** @return array<string, array{encrypted: int, rehashed: int, skipped: int, remaining_plaintext: int}> */
    public function run(bool $dryRun = false, bool $rotate = false, int $batch = 200): array
    {
        $stats = [];

        foreach (self::TARGETS as $table => $spec) {
            $stats[$table] = ['encrypted' => 0, 'rehashed' => 0, 'skipped' => 0, 'remaining_plaintext' => 0];
            $columns = array_merge(array_keys($spec['emails']), $spec['payloads']);

            DB::table($table)->orderBy('id')->select(['id', ...$columns, ...array_values(array_filter($spec['emails']))])->chunkById($batch, function ($rows) use ($table, $spec, $dryRun, $rotate, &$stats): void {
                foreach ($rows as $row) {
                    $update = [];

                    foreach ($spec['emails'] as $column => $hashColumn) {
                        $this->email($row, $column, $hashColumn, $rotate, $update, $stats[$table]);
                    }

                    foreach ($spec['payloads'] as $column) {
                        $this->payload($row, $column, $rotate, $update, $stats[$table]);
                    }

                    if ($update !== [] && ! $dryRun) {
                        DB::table($table)->where('id', $row->id)->update($update);
                    }
                }
            });

            $stats[$table]['remaining_plaintext'] = $this->remaining($table, $spec, $dryRun, $stats[$table]);
        }

        return $stats;
    }

    /** @param array<string, mixed> $update */
    private function email(object $row, string $column, ?string $hashColumn, bool $rotate, array &$update, array &$stats): void
    {
        $value = $row->{$column};

        if ($value === null || $value === '') {
            return;
        }

        $plain = EmailCrypto::tryDecrypt($value);
        $encrypted = EmailCrypto::isEncrypted($value);

        if (! $encrypted || $rotate) {
            $cipher = EmailCrypto::encrypt($plain);

            if (! hash_equals($plain, EmailCrypto::decrypt($cipher))) {
                $stats['skipped']++;

                return;
            }

            $update[$column] = $cipher;
            $stats['encrypted']++;
        }

        if ($hashColumn && ($rotate || $row->{$hashColumn} === null || ! $encrypted)) {
            $update[$hashColumn] = EmailCrypto::hash($plain);
            $stats['rehashed']++;
        }
    }

    /** @param array<string, mixed> $update */
    private function payload(object $row, string $column, bool $rotate, array &$update, array &$stats): void
    {
        $value = $row->{$column};

        if ($value === null || $value === '') {
            return;
        }

        $encrypted = EmailCrypto::isEncrypted($value);

        if ($encrypted && ! $rotate) {
            return;
        }

        $json = EmailCrypto::tryDecrypt($value);

        try {
            json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
            $cipher = EmailCrypto::encrypt($json);

            if (! hash_equals($json, EmailCrypto::decrypt($cipher))) {
                throw new \RuntimeException('round trip failed');
            }
        } catch (Throwable) {
            $stats['skipped']++;

            return;
        }

        $update[$column] = $cipher;
        $stats['encrypted']++;
    }

    /** Rows that still hold readable plaintext after the run (a dry run reports what it would still have to do). */
    private function remaining(string $table, array $spec, bool $dryRun, array $stats): int
    {
        if ($dryRun) {
            return $stats['encrypted'];
        }

        $remaining = 0;

        foreach (array_merge(array_keys($spec['emails']), $spec['payloads']) as $column) {
            DB::table($table)->whereNotNull($column)->where($column, '!=', '')->orderBy('id')->select(['id', $column])->chunkById(500, function ($rows) use ($column, &$remaining): void {
                foreach ($rows as $row) {
                    $remaining += EmailCrypto::isEncrypted($row->{$column}) ? 0 : 1;
                }
            });
        }

        return $remaining;
    }
}
