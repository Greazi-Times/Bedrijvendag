<?php

namespace App\Services\Analytics;

use Illuminate\Support\Carbon;

/** Dutch number, money, date and comparison formatting for the dashboard. */
final class Fmt
{
    public const DASH = '—';

    public static function int(int|float|null $value): string
    {
        return $value === null ? self::DASH : number_format($value, 0, ',', '.');
    }

    public static function dec(int|float|null $value, int $precision = 1): string
    {
        return $value === null ? self::DASH : number_format($value, $precision, ',', '.');
    }

    public static function pct(int|float|null $value): string
    {
        return $value === null ? self::DASH : number_format($value, 1, ',', '.').'%';
    }

    public static function money(?int $cents, string $currency = 'EUR'): string
    {
        if ($cents === null) {
            return self::DASH;
        }

        return ($currency === 'EUR' ? '€' : $currency).' '.number_format($cents / 100, 2, ',', '.');
    }

    /** @param array<string, int> $perCurrency */
    public static function moneyPerCurrency(array $perCurrency): string
    {
        if ($perCurrency === []) {
            return self::money(0);
        }

        return collect($perCurrency)->map(fn (int $cents, string $currency): string => self::money($cents, $currency))->implode(' + ');
    }

    public static function date(Carbon|string|null $date): string
    {
        return $date === null ? self::DASH : Carbon::parse($date)->format('d-m-Y');
    }

    public static function duration(?int $seconds): string
    {
        return match (true) {
            $seconds === null => self::DASH,
            $seconds < 3600 => max(1, intdiv($seconds, 60)).' min',
            $seconds < 172800 => self::dec($seconds / 3600).' uur',
            default => self::dec($seconds / 86400).' dagen',
        };
    }

    /**
     * Absolute and relative change. A zero baseline gives no percentage (never infinity).
     *
     * @return array{absolute: ?float, percent: ?float}
     */
    public static function change(int|float|null $current, int|float|null $baseline): array
    {
        if ($current === null || $baseline === null) {
            return ['absolute' => null, 'percent' => null];
        }

        return ['absolute' => $current - $baseline, 'percent' => $baseline == 0 ? null : round(($current - $baseline) / abs($baseline) * 100, 1)];
    }

    /** "+8 t.o.v. Naam" for a stat card, or null when there is nothing to compare with. */
    public static function versus(int|float|null $current, int|float|null $baseline, ?string $name, bool $higherIsBetter = true): ?array
    {
        $change = self::change($current, $baseline);

        if ($change['absolute'] === null || $name === null) {
            return null;
        }

        $sign = $change['absolute'] > 0 ? '+' : '';
        $text = $sign.self::dec($change['absolute'], fmod($change['absolute'], 1) === 0.0 ? 0 : 1)." t.o.v. {$name}";
        $color = $change['absolute'] == 0 ? 'gray' : (($change['absolute'] > 0) === $higherIsBetter ? 'success' : 'danger');

        return ['text' => $text.($change['percent'] !== null ? ' ('.$sign.self::dec($change['percent']).'%)' : ''), 'color' => $color];
    }
}
