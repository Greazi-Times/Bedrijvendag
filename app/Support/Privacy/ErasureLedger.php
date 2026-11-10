<?php

namespace App\Support\Privacy;

use App\Models\BorrelEnrollment;
use App\Models\Enrollment;
use App\Models\EnrollmentContactChange;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Remembers manual erasures so a restored older database backup can be brought back to the erased state.
 *
 * An erasure row inside the database is lost when an older backup is restored, so every entry is ALSO appended to a
 * file outside the database (config privacy.erasure_ledger.path). Entries hold only a type, an enrollment id or a keyed
 * email hash, and a time; never an address. Entries older than keep_days (longer than the backups) are dropped, so the
 * ledger does not become a permanent list.
 *
 * Replay is idempotent. An enrollment id is only erased when the restored row was created before the erasure, so a new
 * enrollment that reuses the id after a restore is never touched.
 */
class ErasureLedger
{
    public const ENROLLMENT = 'enrollment';

    public const EMAIL = 'email';

    public static function recordEnrollment(int $id): void
    {
        self::record(self::ENROLLMENT, $id, null);
    }

    public static function recordEmail(string $email): void
    {
        self::record(self::EMAIL, null, EmailCrypto::erasureHash($email));
    }

    private static bool $replaying = false;

    private static function record(string $type, ?int $id, ?string $hash): void
    {
        if (self::$replaying) {
            return; // a replay must not write new entries
        }

        $at = now()->toIso8601String();

        try {
            $path = (string) config('privacy.erasure_ledger.path');
            is_dir(dirname($path)) || mkdir(dirname($path), 0700, true);
            file_put_contents($path, json_encode(['type' => $type, 'id' => $id, 'hash' => $hash, 'at' => $at])."\n", FILE_APPEND | LOCK_EX);
            @chmod($path, 0600);
            DB::table('privacy_erasures')->insertOrIgnore(['subject_type' => $type, 'subject_id' => $id, 'subject_hash' => $hash, 'reason' => 'request', 'erased_at' => $at]);
        } catch (Throwable $e) {
            // The erasure itself must not fail because the ledger could not be written, but the gap must be visible.
            report(new \RuntimeException('Erasure ledger write failed ('.$e::class.').'));
        }
    }

    /** @return list<array{type: string, id: ?int, hash: ?string, at: string}> */
    public static function entries(): array
    {
        $entries = [];
        $path = (string) config('privacy.erasure_ledger.path');

        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $row = json_decode($line, true);
                is_array($row) && isset($row['type'], $row['at']) && $entries[] = $row + ['id' => null, 'hash' => null];
            }
        }

        foreach (DB::table('privacy_erasures')->get() as $row) {
            $entries[] = ['type' => $row->subject_type, 'id' => $row->subject_id, 'hash' => $row->subject_hash, 'at' => (string) $row->erased_at];
        }

        return $entries;
    }

    /** @return array<string, int> counts only */
    public static function replay(bool $dryRun = false): array
    {
        $stats = ['enrollments_erased' => 0, 'email_records_erased' => 0];
        self::$replaying = true;

        foreach (self::entries() as $entry) {
            $at = \Illuminate\Support\Carbon::parse($entry['at']);

            if ($entry['type'] === self::ENROLLMENT && $entry['id']) {
                $enrollment = Enrollment::query()->find($entry['id']);

                if ($enrollment && $enrollment->created_at && $enrollment->created_at->lte($at)) {
                    $stats['enrollments_erased']++;
                    $dryRun || $enrollment->delete();
                }
            }

            if ($entry['type'] === self::EMAIL && $entry['hash']) {
                $stats['email_records_erased'] += self::eraseByErasureHash($entry['hash'], $at, $dryRun);
            }
        }

        self::$replaying = false;

        return $stats;
    }

    /** Erases records for an address we know now (privacy request): fast path through the current lookup hashes. */
    public static function eraseEmail(string $email, \Carbon\CarbonInterface $before, bool $dryRun): int
    {
        $count = 0;

        foreach (EmailCrypto::lookupHashes($email) as $hash) {
            $count += self::eraseByHash($hash, $before, $dryRun);
        }

        return $count;
    }

    /**
     * Replay path: the ledger holds only a stable erasure hash, so candidate rows are decrypted in batches and compared.
     * It survives lookup-key rotation because the erasure key is never rotated with the lookup key.
     */
    public static function eraseByErasureHash(string $erasureHash, \Carbon\CarbonInterface $before, bool $dryRun): int
    {
        $count = 0;
        $batch = (int) config('privacy.batch_size');
        $matches = fn (?string $email): bool => $email !== null && hash_equals($erasureHash, (string) EmailCrypto::erasureHash($email));

        NewsletterSubscriber::where('created_at', '<=', $before)->chunkById($batch, function ($rows) use ($matches, $dryRun, &$count): void {
            foreach ($rows as $row) {
                if ($matches($row->email)) {
                    $count++;
                    $dryRun || $row->delete();
                }
            }
        });

        BorrelEnrollment::whereNull('personal_data_purged_at')->where('created_at', '<=', $before)->chunkById($batch, function ($rows) use ($matches, $dryRun, &$count): void {
            foreach ($rows as $row) {
                if ($matches($row->email)) {
                    $count++;
                    $dryRun || $row->forceFill(['name' => null, 'email' => null, 'email_hash' => null, 'personal_data_purged_at' => now()])->saveQuietly();
                }
            }
        });

        EnrollmentContactChange::where('created_at', '<=', $before)->chunkById($batch, function ($rows) use ($matches, $dryRun, &$count): void {
            foreach ($rows as $row) {
                if ($matches($row->email)) {
                    $count++;
                    $dryRun || $row->delete();
                }
            }
        });

        Enrollment::whereNull('personal_data_purged_at')->where('created_at', '<=', $before)->chunkById($batch, function ($rows) use ($matches, $dryRun, &$count): void {
            foreach ($rows as $row) {
                if ($matches($row->contact_email)) {
                    $count++;
                    $dryRun || (new PrivacyRetention)->purge($row);
                }
            }
        });

        return $count;
    }

    private static function eraseByHash(string $hash, \Carbon\CarbonInterface $before, bool $dryRun): int
    {
        $count = 0;

        $count += $dryRun ? NewsletterSubscriber::where('email_hash', $hash)->where('created_at', '<=', $before)->count()
            : NewsletterSubscriber::where('email_hash', $hash)->where('created_at', '<=', $before)->delete();

        $guests = BorrelEnrollment::where('email_hash', $hash)->where('created_at', '<=', $before);
        $count += $dryRun ? $guests->count() : $guests->update(['name' => null, 'email' => null, 'email_hash' => null, 'personal_data_purged_at' => now()]);

        $changes = EnrollmentContactChange::where('email_hash', $hash)->where('created_at', '<=', $before);
        $count += $dryRun ? $changes->count() : $changes->delete();

        Enrollment::where('contact_email_hash', $hash)->where('created_at', '<=', $before)->get()->each(function (Enrollment $e) use (&$count, $dryRun): void {
            $count++;
            $dryRun || (new PrivacyRetention)->purge($e);
        });

        return $count;
    }

    public static function prune(): int
    {
        // Never shorter than the longest restorable backup plus a week of recovery margin.
        $cutoff = now()->subDays(max((int) config('privacy.erasure_ledger.keep_days'), (int) config('privacy.retention.backup_days') + 7));
        $removed = DB::table('privacy_erasures')->where('erased_at', '<', $cutoff)->delete();
        $path = (string) config('privacy.erasure_ledger.path');

        if (is_file($path)) {
            $keep = array_filter(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), fn (string $line): bool => ($row = json_decode($line, true)) && isset($row['at']) && \Illuminate\Support\Carbon::parse($row['at'])->gte($cutoff));
            file_put_contents($path, $keep ? implode("\n", $keep)."\n" : '', LOCK_EX);
        }

        return $removed;
    }
}
