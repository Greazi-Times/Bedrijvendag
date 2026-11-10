<script setup lang="ts">
import { computed, watch } from 'vue';

import { type Answers, emptyValue, type Field, isVisible } from './types';

const props = defineProps<{
    field: Field;
    scope: Answers;
    root: Answers;
    path: string;
    errors: Record<string, string>;
    options: { educations: { id: number; name: string }[]; sectors: { id: number; name: string }[] };
    settings: Record<string, number>;
    fee: string;
    termsHtml: string;
    ui: Record<string, string>;
}>();

// The answers object is shared reactive state owned by the page; fields write into it by design.
const data = computed(() => props.scope);
const id = computed(() => `f-${props.path.replace(/\./g, '-')}`);
const error = computed(() => props.errors[props.path]);
const visible = computed(() => isVisible(props.field, props.scope, props.root));
const describedBy = computed(() => [error.value ? `${id.value}-err` : null, props.field.description ? `${id.value}-help` : null].filter(Boolean).join(' ') || undefined);

const choices = computed(() => {
    if (props.field.options_source) {
        return props.options[props.field.options_source].map((o) => ({ value: String(o.id), label: o.name }));
    }
    return props.field.options ?? [];
});

const maxItems = computed(() => {
    const setting = props.field.max_items_setting ?? props.field.max_setting;
    return setting && props.settings[setting] !== undefined ? props.settings[setting] : (props.field.max_items ?? 10);
});

// A number question can be capped by an edition setting (e.g. the lunch limit).
const numberMax = computed(() => {
    const setting = props.field.max_setting;
    const cap = setting && props.settings[setting] !== undefined ? props.settings[setting] : null;
    const own = props.field.max ?? null;
    return cap !== null && own !== null ? Math.min(cap, own) : (cap ?? own);
});

const rows = computed<Answers[]>(() => (Array.isArray(data.value[props.field.key]) ? data.value[props.field.key] : []));

function addRow() {
    if (rows.value.length >= maxItems.value) return;
    const row: Answers = {};
    props.field.children?.forEach((c) => (row[c.key] = emptyValue(c)));
    data.value[props.field.key] = [...rows.value, row];
}

// A repeater tied to a count question keeps exactly that many entries.
watch(
    () => (props.field.count_from ? props.scope[props.field.count_from] : undefined),
    (count) => {
        if (!props.field.count_from || count === '' || count === null || count === undefined) return;
        const target = Math.min(Math.max(0, Math.floor(Number(count))), maxItems.value);
        if (!Number.isFinite(target)) return;
        while (rows.value.length < target) addRow();
        if (rows.value.length > target) data.value[props.field.key] = rows.value.slice(0, target);
    },
);

function removeRow(i: number) {
    data.value[props.field.key] = rows.value.filter((_, index) => index !== i);
}

function toggle(value: string) {
    const current: string[] = Array.isArray(data.value[props.field.key]) ? data.value[props.field.key] : [];
    data.value[props.field.key] = current.includes(value) ? current.filter((v) => v !== value) : [...current, value];
}

function onFile(event: Event) {
    data.value[props.field.key] = (event.target as HTMLInputElement).files?.[0] ?? null;
}

const inputType = computed(() => ({ email: 'email', phone: 'tel', url: 'url', number: 'number', date: 'date' })[props.field.type] ?? 'text');
</script>

<template>
    <div v-if="visible">
        <p v-if="field.type === 'heading'" class="text-lg font-semibold text-ink">{{ field.label }}</p>
        <div v-else-if="field.type === 'info'" class="alert text-sm">
            <p class="font-semibold">{{ field.label }}</p>
            <p v-if="field.description" class="mt-1 text-ink-muted">{{ field.description }}</p>
        </div>

        <fieldset v-else-if="field.type === 'repeater'" class="rounded-[var(--radius-input)] p-4 ring-1 ring-hairline">
            <legend class="field-label px-1">{{ field.label }} <span v-if="field.required" class="text-danger" aria-hidden="true">*</span></legend>
            <p v-if="field.description" :id="`${id}-help`" class="mb-3 text-xs text-ink-muted">{{ field.description }}</p>
            <div v-for="(row, i) in rows" :key="i" class="mb-4 grid gap-4 rounded-[var(--radius-input)] bg-surface p-4">
                <p class="text-sm font-semibold">#{{ i + 1 }}</p>
                <FormField
                    v-for="child in field.children"
                    :key="child.key"
                    :field="child"
                    :scope="row"
                    :root="root"
                    :path="`${path}.${i}.${child.key}`"
                    :errors="errors"
                    :options="options"
                    :settings="settings"
                    :fee="fee"
                    :terms-html="termsHtml"
                    :ui="ui"
                />
                <button type="button" class="btn btn-secondary justify-self-start" @click="removeRow(i)">{{ ui.remove }}</button>
            </div>
            <button v-if="rows.length < maxItems" type="button" class="btn btn-secondary" @click="addRow">{{ ui.add }}</button>
            <p v-if="error" :id="`${id}-err`" class="field-error mt-2" role="alert">{{ error }}</p>
        </fieldset>

        <div v-else-if="field.type === 'terms'" class="rounded-[var(--radius-input)] p-4 ring-1 ring-hairline">
            <div v-if="termsHtml" class="prose prose-sm mb-4 max-h-56 overflow-y-auto text-sm text-ink-muted" v-html="termsHtml"></div>
            <p class="text-sm font-semibold">{{ ui.accept_fee.replace(':fee', fee) }}</p>
            <p class="mb-3 text-xs text-ink-muted">{{ ui.accept_note }}</p>
            <label class="flex items-start gap-3 text-sm">
                <input :id="id" v-model="data[field.key]" type="checkbox" class="mt-1 h-4 w-4" :aria-invalid="!!error" :aria-describedby="describedBy" />
                <span>{{ field.label }} <span class="text-danger" aria-hidden="true">*</span></span>
            </label>
            <p v-if="error" :id="`${id}-err`" class="field-error mt-2" role="alert">{{ error }}</p>
        </div>

        <fieldset v-else-if="field.type === 'radio' || field.type === 'multiselect'">
            <legend class="field-label mb-2">{{ field.label }} <span v-if="field.required" class="text-danger" aria-hidden="true">*</span></legend>
            <p v-if="field.description" :id="`${id}-help`" class="mb-2 text-xs text-ink-muted">{{ field.description }}</p>
            <div class="grid gap-2 sm:grid-cols-2">
                <label v-for="o in choices" :key="o.value" class="flex items-center gap-3 rounded-[var(--radius-input)] bg-canvas px-4 py-3 text-sm ring-1 ring-hairline">
                    <input
                        v-if="field.type === 'radio'"
                        v-model="data[field.key]"
                        type="radio"
                        :name="id"
                        :value="o.value"
                        class="h-4 w-4"
                    />
                    <input v-else type="checkbox" class="h-4 w-4" :checked="(data[field.key] ?? []).includes(o.value)" @change="toggle(o.value)" />
                    {{ o.label }}
                </label>
            </div>
            <p v-if="error" :id="`${id}-err`" class="field-error mt-2" role="alert">{{ error }}</p>
        </fieldset>

        <div v-else-if="field.type === 'checkbox'">
            <label class="flex items-start gap-3 text-sm">
                <input :id="id" v-model="data[field.key]" type="checkbox" class="mt-1 h-4 w-4" :aria-describedby="describedBy" />
                <span>{{ field.label }}</span>
            </label>
            <p v-if="error" :id="`${id}-err`" class="field-error mt-2" role="alert">{{ error }}</p>
        </div>

        <div v-else>
            <label :for="id" class="field-label mb-2 block">{{ field.label }} <span v-if="field.required" class="text-danger" aria-hidden="true">*</span></label>
            <p v-if="field.description" :id="`${id}-help`" class="mb-2 text-xs text-ink-muted">{{ field.description }}</p>
            <textarea v-if="field.type === 'textarea'" :id="id" v-model="data[field.key]" rows="5" :placeholder="field.placeholder" class="input" :aria-invalid="!!error" :aria-describedby="describedBy"></textarea>
            <select v-else-if="field.type === 'select'" :id="id" v-model="data[field.key]" class="input" :aria-invalid="!!error" :aria-describedby="describedBy">
                <option value="">{{ ui.choose }}</option>
                <option v-for="o in choices" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
            <input v-else-if="field.type === 'file'" :id="id" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="input" :aria-invalid="!!error" :aria-describedby="describedBy" @change="onFile" />
            <input
                v-else
                :id="id"
                v-model="data[field.key]"
                :type="inputType"
                :placeholder="field.placeholder"
                :min="field.type === 'number' ? (field.min ?? undefined) : undefined"
                :max="field.type === 'number' ? (numberMax ?? undefined) : undefined"
                class="input"
                :aria-invalid="!!error"
                :aria-describedby="describedBy"
            />
            <p v-if="error" :id="`${id}-err`" class="field-error mt-2" role="alert">{{ error }}</p>
        </div>
    </div>
</template>
