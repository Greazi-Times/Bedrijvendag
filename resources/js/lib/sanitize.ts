/**
 * Client-side safety net for rich text (HTML) stored by the Filament
 * RichEditor. Content should also be sanitized server-side.
 */
export function sanitizeHtml(value: string | null | undefined): string {
    try {
        const doc = new DOMParser().parseFromString(String(value ?? ''), 'text/html');

        // Remove dangerous elements
        doc.querySelectorAll('script, style, iframe, object, embed, link, meta').forEach((el) => el.remove());

        // Remove event handlers and javascript: URLs
        for (const el of doc.body.querySelectorAll('*')) {
            for (const attr of Array.from(el.attributes)) {
                const name = attr.name.toLowerCase();
                const val = (attr.value ?? '').trim().toLowerCase();

                if (name.startsWith('on') || ((name === 'href' || name === 'src') && val.startsWith('javascript:'))) {
                    el.removeAttribute(attr.name);
                }
            }
        }

        return doc.body.innerHTML;
    } catch {
        return String(value ?? '');
    }
}

/** Plain-text preview of rich text, for short card descriptions. */
export function htmlToText(value: string | null | undefined): string {
    if (!value) return '';

    try {
        const doc = new DOMParser().parseFromString(value, 'text/html');
        return (doc.body.textContent ?? '').trim();
    } catch {
        return value.replace(/<[^>]*>/g, '').trim();
    }
}
