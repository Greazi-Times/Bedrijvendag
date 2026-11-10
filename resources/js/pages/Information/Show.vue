<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { PhCheckCircle, PhWarningCircle } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';

import FormField from '@/components/enrollment/FormField.vue';
import ProfileVerification, { type Verification } from '@/components/enrollment/ProfileVerification.vue';
import { type Answers, emptyValue, type Field, type Section } from '@/components/enrollment/types';
import PageIntro from '@/components/site/PageIntro.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';

const props = defineProps<{
    state: 'ready' | 'invalid' | 'expired' | 'revoked' | 'closed' | 'locked';
    stateMessage?: string | null;
    event?: { name: string; date: string | null };
    company?: { name: string };
    form?: { version_id: number; schema: { sections: Section[] } } | null;
    initial?: Answers;
    verification?: {
        current: {
            name: string;
            description: string;
            description_html: string;
            website_url: string | null;
            logo_url: string | null;
            educations: string[];
            sectors: string[];
            education_ids: number[];
            sector_ids: number[];
        };
        choice: string | null;
        proposal: {
            name?: string | null;
            description?: string | null;
            website_url?: string | null;
            education_ids?: number[];
            sector_ids?: number[];
            new_sector_names?: string[];
        } | null;
    };
    options?: { educations: { id: number; name: string }[]; sectors: { id: number; name: string }[] };
    settings?: Record<string, number>;
    submitted?: { revision: number; at: string } | null;
    editableUntil?: string | null;
    saved?: boolean;
    urls?: { store: string };
    ui: Record<string, string>;
}>();

const sections = computed(() => props.form?.schema.sections ?? []);
const fmt = (iso: string) => new Intl.DateTimeFormat('nl-NL', { dateStyle: 'long', timeStyle: 'short' }).format(new Date(iso));

function initialAnswers(): Answers {
    const answers: Answers = {};
    sections.value
        .flatMap((s) => s.fields)
        .forEach((f: Field) => {
            const stored = props.initial?.[f.key];
            answers[f.key] = stored !== undefined && stored !== null ? stored : emptyValue(f);
        });
    return answers;
}

const proposal = props.verification?.proposal;
const stored = props.verification?.current;

const verification = ref<Verification>({
    choice: (props.verification?.choice ?? '') as Verification['choice'],
    fields: {
        name: proposal?.name ?? stored?.name ?? '',
        description: null,
        website_url: proposal?.website_url ?? stored?.website_url ?? '',
        education_ids: [...(proposal?.education_ids ?? stored?.education_ids ?? [])],
        sector_ids: [...(proposal?.sector_ids ?? stored?.sector_ids ?? [])],
        new_sector_names: [...(proposal?.new_sector_names ?? [])],
        logo: null,
    },
});

const form = useForm({
    form_version_id: props.form?.version_id ?? 0,
    answers: initialAnswers() as Answers,
});

// What is sent: a confirmation is just the choice; a correction sends the profile fields, and the description only
// when it was really edited (so the editor's own re-serialisation never looks like a change).
form.transform((data) => {
    const v = verification.value;
    const payload: Record<string, unknown> = { choice: v.choice || undefined };

    if (v.choice === 'correction') {
        Object.assign(payload, {
            name: v.fields.name,
            website_url: v.fields.website_url,
            education_ids: v.fields.education_ids,
            sector_ids: v.fields.sector_ids,
            new_sector_names: v.fields.new_sector_names,
            logo: v.fields.logo ?? undefined,
        });
        if (v.fields.description !== null) payload.description = v.fields.description;
    }

    return { ...data, verification: payload };
});

const errors = computed<Record<string, string>>(() =>
    Object.fromEntries(Object.entries(form.errors).map(([k, v]) => [k.replace(/^answers\./, ''), v as string])),
);

function submit() {
    form.post(props.urls?.store ?? '', {
        forceFormData: true,
        preserveScroll: true,
        onError: () => document.querySelector<HTMLElement>('[role="alert"], [aria-invalid="true"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' }),
        onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    });
}
</script>

<template>
    <Head :title="ui.title" />

    <SiteLayout>
        <PageIntro :eyebrow="event?.name ?? null" :title="ui.title" :lead="company ? `${ui.company}: ${company.name}` : null" />

        <section class="site-container pt-14 pb-24 md:pt-20 md:pb-32">
            <div class="mx-auto max-w-3xl">
                <div v-if="state !== 'ready' || !form" class="alert" role="status">
                    <div class="flex items-start gap-3">
                        <PhWarningCircle class="mt-0.5 h-5 w-5 shrink-0" />
                        <p>{{ stateMessage }}</p>
                    </div>
                </div>

                <template v-else>
                    <div v-if="saved" class="alert alert-success mb-6" role="status" aria-live="polite">
                        <div class="flex items-start gap-3">
                            <PhCheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-success" />
                            <div>
                                <p class="font-semibold">{{ ui.saved_title }}</p>
                                <p class="text-sm">{{ ui.saved_body }}</p>
                            </div>
                        </div>
                    </div>
                    <p v-if="submitted && !saved" class="mb-2 text-sm text-ink-muted">{{ ui.submitted_on.replace(':date', fmt(submitted.at)) }}</p>
                    <p v-if="editableUntil" class="mb-6 text-sm text-ink-muted">{{ ui.editable_until.replace(':date', fmt(editableUntil)) }}</p>

                    <form class="card p-6 sm:p-10" novalidate @submit.prevent="submit">
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
                                    <div v-if="field.maps_to === 'company.identity'" class="alert text-sm">
                                        <p class="font-semibold">{{ field.label }}: {{ company?.name }}</p>
                                        <p v-if="field.description" class="mt-1 text-ink-muted">{{ field.description }}</p>
                                    </div>
                                    <ProfileVerification
                                        v-else-if="field.maps_to === 'company.verification' && props.verification && options"
                                        v-model="verification"
                                        :label="field.label ?? ui.verify_title"
                                        :help="field.description"
                                        :required="field.required"
                                        :current="props.verification.current"
                                        :initial-description="props.verification.proposal?.description"
                                        :options="options"
                                        :errors="errors"
                                        :ui="ui"
                                    />
                                    <FormField
                                        v-else
                                        :field="field"
                                        :scope="form.answers"
                                        :root="form.answers"
                                        :path="field.key"
                                        :errors="errors"
                                        :options="options ?? { educations: [], sectors: [] }"
                                        :settings="settings ?? {}"
                                        fee=""
                                        terms-html=""
                                        :ui="ui"
                                    />
                                </template>
                            </fieldset>
                        </div>

                        <div class="mt-10">
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                {{ form.processing ? ui.submitting : submitted ? ui.update : ui.submit }}
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </section>
    </SiteLayout>
</template>
