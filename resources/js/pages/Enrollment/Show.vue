<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { PhBuildings, PhMagnifyingGlass, PhWarningCircle } from '@phosphor-icons/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

import FormField from '@/components/enrollment/FormField.vue';
import { type Answers, emptyValue, type Field, isVisible, type Section } from '@/components/enrollment/types';
import PageIntro from '@/components/site/PageIntro.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';

type Hit = { id: number; name: string; website_url: string | null; exact: boolean };
type Profile = { id: number; name: string; website_url: string | null; description: string; logo_url: string | null; education_ids: number[]; sector_ids: number[] };

const props = defineProps<{
    event: { name: string; date: string | null };
    state: 'open' | 'disabled' | 'not_open_yet' | 'closed';
    closedMessage: string;
    form: { version_id: number; schema: { sections: Section[] } } | null;
    fee: { cents: number; currency: string };
    termsHtml: string;
    options: { educations: { id: number; name: string }[]; sectors: { id: number; name: string }[] };
    settings: Record<string, number>;
    submissionKey: string;
    urls: { store: string; search: string; company: string };
    ui: Record<string, string>;
}>();

const sections = computed(() => props.form?.schema.sections ?? []);
const allFields = computed(() => sections.value.flatMap((s) => s.fields));
const keyFor = (map: string) => allFields.value.find((f) => f.maps_to === map)?.key;

const feeLabel = computed(() => new Intl.NumberFormat('nl-NL', { style: 'currency', currency: props.fee.currency }).format(props.fee.cents / 100));

function initialAnswers(): Answers {
    const answers: Answers = {};
    allFields.value.forEach((f) => (answers[f.key] = emptyValue(f)));
    return answers;
}

const form = useForm({
    submission_key: props.submissionKey,
    form_version_id: props.form?.version_id ?? 0,
    selected_company_id: null as number | null,
    answers: initialAnswers() as Answers,
});

const errors = computed<Record<string, string>>(() =>
    Object.fromEntries(Object.entries(form.errors).map(([k, v]) => [k.replace(/^answers\./, ''), v as string])),
);

// --- existing company search (names only; selecting prefills public data) ---
const hits = ref<Hit[]>([]);
const searching = ref(false);
const selected = ref<Profile | null>(null);
const dismissed = ref('');
let timer: ReturnType<typeof setTimeout> | undefined;
let controller: AbortController | undefined;

const nameKey = computed(() => keyFor('company.name') ?? '');
const typedName = computed<string>(() => String(form.answers[nameKey.value] ?? ''));

watch(typedName, (value) => {
    clearTimeout(timer);
    if (selected.value && value !== selected.value.name) clearSelection(false);
    if (value.trim().length < 2 || value === selected.value?.name) {
        hits.value = [];
        return;
    }
    timer = setTimeout(async () => {
        controller?.abort();
        controller = new AbortController();
        searching.value = true;
        try {
            const res = await fetch(`${props.urls.search}?q=${encodeURIComponent(value.trim())}`, { headers: { Accept: 'application/json' }, signal: controller.signal });
            hits.value = res.ok ? await res.json() : [];
        } catch {
            /* aborted or offline: keep typing, the form still works for a new company */
        } finally {
            searching.value = false;
        }
    }, 250);
});

onBeforeUnmount(() => {
    clearTimeout(timer);
    controller?.abort();
});

async function choose(hit: Hit) {
    const res = await fetch(props.urls.company.replace('__ID__', String(hit.id)), { headers: { Accept: 'application/json' } });
    if (!res.ok) return;
    const profile: Profile = await res.json();
    selected.value = profile;
    form.selected_company_id = profile.id;
    hits.value = [];
    const set = (map: string, value: unknown) => {
        const k = keyFor(map);
        if (k) form.answers[k] = value;
    };
    set('company.name', profile.name);
    set('company.website', profile.website_url ?? '');
    set('company.description', profile.description);
    set('company.educations', profile.education_ids.map(String));
    set('company.sectors', profile.sector_ids.map(String));
}

function clearSelection(resetName = true) {
    selected.value = null;
    form.selected_company_id = null;
    if (resetName && nameKey.value) form.answers[nameKey.value] = '';
}

// --- progress: share of visible required top-level questions that are filled ---
const progress = computed(() => {
    const required = allFields.value.filter((f) => f.required && !['info', 'heading', 'repeater'].includes(f.type) && isVisible(f, form.answers, form.answers));
    if (!required.length) return 100;
    const done = required.filter((f) => {
        const v = form.answers[f.key];
        return Array.isArray(v) ? v.length > 0 : !!v;
    });
    return Math.round((done.length / required.length) * 100);
});

const logoPreview = computed(() => {
    const k = keyFor('company.logo');
    const file = k ? form.answers[k] : null;
    return file instanceof File ? URL.createObjectURL(file) : (selected.value?.logo_url ?? null);
});

function submit() {
    form.post(props.urls.store, {
        forceFormData: true,
        preserveScroll: true,
        onError: () => document.querySelector<HTMLElement>('[role="alert"], [aria-invalid="true"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' }),
    });
}

const isNameField = (f: Field) => f.maps_to === 'company.name';
</script>

<template>
    <Head :title="`${ui.title} – ${event.name}`" />

    <SiteLayout>
        <PageIntro :eyebrow="event.name" :title="ui.title" />

        <section class="site-container pt-14 pb-24 md:pt-20 md:pb-32">
            <div class="mx-auto max-w-3xl">
                <div v-if="state !== 'open' || !form" class="alert" role="status">
                    <div class="flex items-start gap-3">
                        <PhWarningCircle class="mt-0.5 h-5 w-5 shrink-0" />
                        <p>{{ closedMessage }}</p>
                    </div>
                </div>

                <form v-else class="card p-6 sm:p-10" novalidate @submit.prevent="submit">
                    <div class="mb-8">
                        <div class="flex items-center justify-between text-xs text-ink-muted">
                            <span>{{ ui.fee }}: <strong class="text-ink">{{ feeLabel }}</strong></span>
                            <span>{{ progress }}%</span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full bg-brand transition-all" :style="{ width: `${progress}%` }"></div>
                        </div>
                    </div>

                    <div v-if="errors.form" class="alert mb-6" role="alert">
                        <p class="text-sm font-semibold">{{ errors.form }}</p>
                    </div>
                    <div v-else-if="form.hasErrors" class="alert mb-6" role="alert">
                        <p class="text-sm font-semibold">{{ ui.form_error }}</p>
                    </div>

                    <div class="grid gap-10">
                        <fieldset v-for="(section, index) in sections" :key="section.key" class="grid gap-6">
                            <legend class="mb-1 text-xl font-semibold text-ink">
                                <span class="mr-2 text-sm text-ink-muted">{{ index + 1 }}/{{ sections.length }}</span>{{ section.title }}
                            </legend>
                            <p v-if="section.description" class="-mt-4 text-sm text-ink-muted">{{ section.description }}</p>

                            <template v-for="field in section.fields" :key="field.key">
                                <div v-if="isNameField(field)">
                                    <label :for="`f-${field.key}`" class="field-label mb-2 block">{{ field.label }} <span class="text-danger" aria-hidden="true">*</span></label>
                                    <div class="relative">
                                        <PhMagnifyingGlass class="pointer-events-none absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-ink-muted" />
                                        <input
                                            :id="`f-${field.key}`"
                                            v-model="form.answers[field.key]"
                                            type="text"
                                            autocomplete="off"
                                            role="combobox"
                                            :aria-expanded="hits.length > 0"
                                            :aria-invalid="!!errors[field.key]"
                                            :placeholder="ui.search_placeholder"
                                            class="input pl-11"
                                        />
                                    </div>
                                    <p class="mt-2 text-xs text-ink-muted">{{ field.description || ui.search_hint }}</p>
                                    <p v-if="searching" class="mt-2 text-xs text-ink-muted" aria-live="polite">…</p>

                                    <ul v-if="hits.length && dismissed !== typedName" class="mt-3 grid gap-2" role="listbox">
                                        <li v-for="hit in hits" :key="hit.id">
                                            <button type="button" class="block w-full rounded-[var(--radius-input)] bg-canvas px-4 py-3 text-left text-sm ring-1 ring-hairline transition hover:bg-surface" @click="choose(hit)">
                                                <span class="font-semibold">{{ hit.name }}</span>
                                                <span v-if="hit.website_url" class="mt-1 block text-xs text-ink-muted">{{ hit.website_url }}</span>
                                                <span class="mt-1 block text-xs font-semibold text-brand-ink">{{ ui.use_existing }}</span>
                                            </button>
                                        </li>
                                        <li><button type="button" class="text-xs font-semibold underline" @click="dismissed = typedName">{{ ui.not_listed }}</button></li>
                                    </ul>

                                    <div v-if="selected" class="alert mt-3 flex items-center justify-between gap-3 text-sm">
                                        <span class="flex items-center gap-2"><PhBuildings class="h-5 w-5" />{{ ui.selected }}: <strong>{{ selected.name }}</strong></span>
                                        <button type="button" class="text-xs font-semibold underline" @click="clearSelection()">{{ ui.clear }}</button>
                                    </div>
                                    <p v-if="errors[field.key]" class="field-error mt-2" role="alert">{{ errors[field.key] }}</p>
                                    <p v-if="form.errors.selected_company_id" class="field-error mt-2" role="alert">{{ form.errors.selected_company_id }}</p>
                                </div>

                                <div v-else>
                                    <FormField
                                        :field="field"
                                        :scope="form.answers"
                                        :root="form.answers"
                                        :path="field.key"
                                        :errors="errors"
                                        :options="options"
                                        :settings="settings"
                                        :fee="feeLabel"
                                        :terms-html="termsHtml"
                                        :ui="ui"
                                    />
                                    <div v-if="field.maps_to === 'company.logo' && logoPreview" class="mt-3 flex items-center gap-3 text-xs text-ink-muted">
                                        <img :src="logoPreview" alt="" class="h-16 w-16 rounded-[var(--radius-input)] bg-canvas object-contain ring-1 ring-hairline" />
                                        <span>{{ ui.logo_current }}</span>
                                    </div>
                                </div>
                            </template>
                        </fieldset>
                    </div>

                    <div class="mt-10">
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ form.processing ? ui.submitting : ui.submit }}</button>
                    </div>
                </form>
            </div>
        </section>
    </SiteLayout>
</template>
