export type Option = { value: string; label: string };

export type Field = {
    key: string;
    type: string;
    label?: string;
    description?: string;
    placeholder?: string;
    required?: boolean;
    default?: string | null;
    options?: Option[];
    options_source?: 'educations' | 'sectors' | null;
    min?: number | null;
    max?: number | null;
    max_items?: number | null;
    max_items_setting?: string | null;
    max_setting?: string | null;
    count_from?: string | null;
    maps_to?: string | null;
    children?: Field[];
    visible_if?: { field: string; operator: string; value?: string | string[] | null } | null;
};

export type Section = { key: string; title: string; description?: string; fields: Field[] };

export type Answers = Record<string, any>;

/** Mirrors the server's FormEngine::visible so conditional questions show and hide live. */
export function isVisible(field: Field, scope: Answers, root: Answers): boolean {
    const cond = field.visible_if;
    if (!cond || !cond.field) return true;

    const actual = cond.field in scope ? scope[cond.field] : root[cond.field];
    const has = (v: unknown) => (Array.isArray(actual) ? actual.map(String).includes(String(v)) : String(actual ?? '') === String(v));
    const wanted = cond.value ?? null;

    switch (cond.operator) {
        case 'eq':
            return has(wanted);
        case 'neq':
            return !has(wanted);
        case 'in':
            return ([] as unknown[]).concat(wanted ?? []).some(has);
        case 'filled':
            return Array.isArray(actual) ? actual.length > 0 : !!actual;
        case 'empty':
            return Array.isArray(actual) ? actual.length === 0 : !actual;
        default:
            return true;
    }
}

export function emptyValue(field: Field): unknown {
    switch (field.type) {
        case 'multiselect':
            return [];
        case 'repeater':
            return [];
        case 'checkbox':
        case 'terms':
            return false;
        case 'file':
            return null;
        default:
            return field.default ?? '';
    }
}
