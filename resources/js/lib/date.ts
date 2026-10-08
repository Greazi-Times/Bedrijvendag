/**
 * Formats an ISO-ish date string (YYYY-MM-DD or full ISO) for display.
 * Date-only values are parsed as local dates so they never shift a day.
 * Falls back to the raw value when it cannot be parsed.
 */
export function formatDate(
    value: string | null | undefined,
    locale: string,
    options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' },
): string | null {
    if (!value) return null;

    const dateOnly = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    const date = dateOnly ? new Date(Number(dateOnly[1]), Number(dateOnly[2]) - 1, Number(dateOnly[3])) : new Date(value);

    if (Number.isNaN(date.getTime())) return value;

    return new Intl.DateTimeFormat(locale, options).format(date);
}
