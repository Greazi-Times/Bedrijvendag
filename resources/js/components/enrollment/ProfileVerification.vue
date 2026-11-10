<script lang="ts">
export type Verification = {
    choice: '' | 'confirmed' | 'correction';
    fields: ProfileFields;
};
</script>

<script setup lang="ts">
import { computed } from 'vue';

import CompanyProfileFields, { type ProfileFields } from './CompanyProfileFields.vue';

type Current = {
    name: string;
    description: string;
    description_html: string;
    website_url: string | null;
    logo_url: string | null;
    educations: string[];
    sectors: string[];
};

const props = defineProps<{
    label: string;
    help?: string;
    required?: boolean;
    current: Current;
    initialDescription?: string | null;
    options: { educations: { id: number; name: string }[]; sectors: { id: number; name: string }[] };
    errors: Record<string, string>;
    ui: Record<string, string>;
}>();

// Shared reactive state owned by the page; this component edits it in place.
const model = defineModel<Verification>({ required: true });
const state = computed(() => model.value);
const fields = computed({ get: () => state.value.fields, set: (value) => (state.value.fields = value) });
</script>

<template>
    <fieldset class="rounded-[var(--radius-input)] p-5 ring-1 ring-hairline">
        <legend class="field-label px-1">{{ label }} <span v-if="required" class="text-danger" aria-hidden="true">*</span></legend>
        <p v-if="help" class="mb-3 text-sm text-ink-muted">{{ help }}</p>
        <p class="mb-4 text-sm text-ink-muted">{{ ui.verify_intro }}</p>

        <dl class="grid gap-3 rounded-[var(--radius-input)] bg-surface p-4 text-sm">
            <div>
                <dt class="text-xs text-ink-muted">{{ ui.name }}</dt>
                <dd class="font-semibold">{{ current.name }}</dd>
            </div>
            <div v-if="current.logo_url">
                <dt class="text-xs text-ink-muted">{{ ui.logo }}</dt>
                <dd><img :src="current.logo_url" alt="" class="mt-1 h-16 w-16 rounded-[var(--radius-input)] bg-canvas object-contain ring-1 ring-hairline" /></dd>
            </div>
            <div>
                <dt class="text-xs text-ink-muted">{{ ui.description }}</dt>
                <dd class="whitespace-pre-line">{{ current.description || ui.none }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink-muted">{{ ui.website }}</dt>
                <dd>{{ current.website_url || ui.none }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink-muted">{{ ui.educations }}</dt>
                <dd>{{ current.educations.join(', ') || ui.none }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink-muted">{{ ui.sectors }}</dt>
                <dd>{{ current.sectors.join(', ') || ui.none }}</dd>
            </div>
        </dl>

        <div class="mt-4 grid gap-2 sm:grid-cols-2" role="radiogroup" :aria-label="label">
            <label class="flex items-center gap-3 rounded-[var(--radius-input)] bg-canvas px-4 py-3 text-sm ring-1 ring-hairline">
                <input v-model="state.choice" type="radio" value="confirmed" name="verification-choice" class="h-4 w-4" />
                {{ ui.verify_confirm }}
            </label>
            <label class="flex items-center gap-3 rounded-[var(--radius-input)] bg-canvas px-4 py-3 text-sm ring-1 ring-hairline">
                <input v-model="state.choice" type="radio" value="correction" name="verification-choice" class="h-4 w-4" />
                {{ ui.verify_correct }}
            </label>
        </div>
        <p v-if="errors['verification.choice']" class="field-error mt-2" role="alert">{{ errors['verification.choice'] }}</p>

        <div v-if="state.choice === 'correction'" class="mt-5 grid gap-5">
            <p class="text-xs text-ink-muted">{{ ui.verify_note }}</p>
            <CompanyProfileFields v-model="fields" :current="props.current" :initial-description="initialDescription" :options="options" :errors="errors" prefix="verification." />
        </div>
    </fieldset>
</template>
