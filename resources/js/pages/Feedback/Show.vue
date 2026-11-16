<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { PhCheckCircle, PhLockSimple, PhWarningCircle } from '@phosphor-icons/vue';
import { computed } from 'vue';

import FormField from '@/components/enrollment/FormField.vue';
import { type Answers, emptyValue, type Section } from '@/components/enrollment/types';
import PageIntro from '@/components/site/PageIntro.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';

const props = defineProps<{
    state: 'open' | 'not_open_yet' | 'closed';
    stateMessage?: string | null;
    event: { name: string };
    audience: { key: string; label: string };
    intro?: string;
    minutes?: number;
    form?: { version_id: number; schema: { sections: Section[] } } | null;
    submitted?: boolean;
    urls?: { store: string };
    ui: Record<string, string>;
}>();

const sections = computed(() => props.form?.schema.sections ?? []);

const answers: Answers = {};
sections.value.flatMap((s) => s.fields).forEach((f) => (answers[f.key] = emptyValue(f)));

// "website" is a honeypot: invisible to people, tempting to bots.
const form = useForm({ form_version_id: props.form?.version_id ?? 0, answers, website: '' });

const errors = computed<Record<string, string>>(() => Object.fromEntries(Object.entries(form.errors).map(([k, v]) => [k.replace(/^answers\./, ''), v as string])));

function submit() {
    form.post(props.urls?.store ?? '', {
        preserveScroll: true,
        onError: () => document.querySelector<HTMLElement>('[role="alert"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' }),
        onSuccess: () => window.scrollTo({ top: 0 }),
    });
}
</script>

<template>
    <Head :title="`${ui.title} · ${event.name}`" />

    <SiteLayout>
        <PageIntro :eyebrow="`${event.name} · ${audience.label}`" :title="ui.title" :lead="state === 'open' && !submitted ? intro : null" />

        <section class="site-container pt-10 pb-24 md:pt-16 md:pb-32">
            <div class="mx-auto max-w-2xl">
                <div v-if="submitted" class="alert alert-success" role="status" aria-live="polite">
                    <div class="flex items-start gap-3">
                        <PhCheckCircle class="mt-0.5 h-6 w-6 shrink-0 text-success" />
                        <div>
                            <p class="text-lg font-semibold">{{ ui.thanks_title }}</p>
                            <p class="mt-1 text-sm">{{ ui.thanks_body }}</p>
                        </div>
                    </div>
                </div>

                <div v-else-if="state !== 'open' || !form" class="alert" role="status">
                    <div class="flex items-start gap-3">
                        <PhWarningCircle class="mt-0.5 h-5 w-5 shrink-0" />
                        <p>{{ stateMessage }}</p>
                    </div>
                </div>

                <form v-else class="card p-6 sm:p-10" novalidate @submit.prevent="submit">
                    <p class="mb-1 text-sm text-ink-muted">{{ ui.estimate.replace(':minutes', String(minutes ?? 2)) }} {{ ui.required_note }}</p>
                    <p class="mb-8 flex items-start gap-2 text-sm text-ink-muted"><PhLockSimple class="mt-0.5 h-4 w-4 shrink-0" />{{ ui.privacy }}</p>

                    <div v-if="errors.form" class="alert mb-6" role="alert">
                        <p class="text-sm font-semibold">{{ errors.form }}</p>
                    </div>
                    <div v-else-if="form.hasErrors" class="alert mb-6" role="alert">
                        <p class="text-sm font-semibold">{{ ui.form_error }}</p>
                    </div>

                    <div class="grid gap-10">
                        <fieldset v-for="section in sections" :key="section.key" class="grid gap-7">
                            <legend v-if="sections.length > 1" class="mb-1 text-xl font-semibold text-ink">{{ section.title }}</legend>
                            <p v-if="section.description" class="-mt-4 text-sm text-ink-muted">{{ section.description }}</p>
                            <FormField
                                v-for="field in section.fields"
                                :key="field.key"
                                :field="field"
                                :scope="form.answers"
                                :root="form.answers"
                                :path="field.key"
                                :errors="errors"
                                :options="{ educations: [], sectors: [] }"
                                :settings="{}"
                                fee=""
                                terms-html=""
                                :ui="ui"
                            />
                        </fieldset>
                    </div>

                    <div aria-hidden="true" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden">
                        <label>Website <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" /></label>
                    </div>

                    <div class="mt-10">
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ form.processing ? ui.submitting : ui.submit }}</button>
                    </div>
                </form>
            </div>
        </section>
    </SiteLayout>
</template>
