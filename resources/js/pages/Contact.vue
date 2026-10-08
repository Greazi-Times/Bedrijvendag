<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { PhArrowUpRight, PhCheckCircle, PhEnvelopeSimple, PhMapPin, PhPaperPlaneTilt, PhPhone } from '@phosphor-icons/vue';
import { onBeforeUnmount, ref } from 'vue';

import PageIntro from '@/components/site/PageIntro.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import { useTranslations } from '@/i18n';
import { focusFirstError } from '@/lib/forms';

const { t } = useTranslations();

const form = useForm({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
});

const showSuccess = ref(false);
let successTimeout: number | undefined;

const triggerSuccess = () => {
    showSuccess.value = true;

    if (successTimeout) window.clearTimeout(successTimeout);

    successTimeout = window.setTimeout(() => {
        showSuccess.value = false;
    }, 7000);
};

onBeforeUnmount(() => {
    if (successTimeout) window.clearTimeout(successTimeout);
});

const submit = () => {
    form.post('/contact', {
        preserveScroll: true,
        onError: focusFirstError,
        onSuccess: () => {
            triggerSuccess();
            form.reset('name', 'email', 'phone', 'subject', 'message');
        },
    });
};

const fields = [
    { id: 'name', type: 'text', autocomplete: 'name', label: 'common.name', placeholder: 'contact.fullName', required: true },
    { id: 'email', type: 'email', autocomplete: 'email', label: 'common.email', placeholder: 'contact.emailExample', required: true },
    { id: 'phone', type: 'tel', autocomplete: 'tel', label: 'contact.phoneOptional', placeholder: null, required: false },
    { id: 'subject', type: 'text', autocomplete: 'off', label: 'contact.subject', placeholder: 'contact.subjectPlaceholder', required: true },
] as const;
</script>

<template>
    <Head :title="t('nav.contact')" />

    <SiteLayout>
        <PageIntro :eyebrow="t('nav.contact')" :title="t('contact.title')" :lead="t('contact.intro')">
            <template #aside>
                <ul class="grid gap-2">
                    <li>
                        <a
                            href="mailto:bedrijvendag.atix@avans.nl"
                            class="group flex items-center gap-4 rounded-[var(--radius-card)] bg-canvas p-4 shadow-raised ring-1 ring-hairline transition-colors hover:bg-surface"
                        >
                            <span class="tile-sunset flex size-11 shrink-0 items-center justify-center rounded-xl"
                                ><PhEnvelopeSimple :size="20" weight="bold" aria-hidden="true"
                            /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm text-ink-muted">{{ t('common.email') }}</span>
                                <span class="block truncate font-medium text-ink">bedrijvendag.atix@avans.nl</span>
                            </span>
                            <PhArrowUpRight
                                :size="16"
                                weight="bold"
                                class="text-ink-muted transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                aria-hidden="true"
                            />
                        </a>
                    </li>
                    <li>
                        <a
                            href="tel:+31885258600"
                            class="group flex items-center gap-4 rounded-[var(--radius-card)] bg-canvas p-4 shadow-raised ring-1 ring-hairline transition-colors hover:bg-surface"
                        >
                            <span class="tile-electric flex size-11 shrink-0 items-center justify-center rounded-xl"><PhPhone :size="20" weight="bold" aria-hidden="true" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm text-ink-muted">{{ t('common.phone') }}</span>
                                <span class="t-mono block font-medium text-ink">088-5258600</span>
                            </span>
                            <PhArrowUpRight
                                :size="16"
                                weight="bold"
                                class="text-ink-muted transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                aria-hidden="true"
                            />
                        </a>
                    </li>
                    <li class="flex items-center gap-4 rounded-[var(--radius-card)] bg-canvas p-4 shadow-raised ring-1 ring-hairline">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-surface text-ink"
                            ><PhMapPin :size="20" weight="bold" aria-hidden="true"
                        /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm text-ink-muted">{{ t('contact.address') }}</span>
                            <span class="block font-medium text-ink">{{ t('contact.addressValue') }}</span>
                        </span>
                    </li>
                </ul>
            </template>
        </PageIntro>

        <section id="support" class="site-container pt-16 pb-24 md:pt-24 md:pb-32">
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-16">
                <div v-reveal class="lg:col-span-4">
                    <h2 class="t-h2 text-ink">{{ t('common.message') }}</h2>
                    <p class="t-lead mt-5">{{ t('contact.direct') }}</p>
                </div>

                <div v-reveal="80" class="card p-6 sm:p-10 lg:col-span-8">
                    <form class="grid gap-6 sm:grid-cols-2" @submit.prevent="submit">
                        <div v-for="field in fields" :key="field.id" class="field">
                            <label :for="field.id" class="field-label"> {{ t(field.label) }}<span v-if="field.required" class="text-danger" aria-hidden="true"> *</span> </label>
                            <input
                                :id="field.id"
                                v-model="form[field.id]"
                                :type="field.type"
                                :name="field.id"
                                :autocomplete="field.autocomplete"
                                :placeholder="field.placeholder ? t(field.placeholder) : '+31 6 12345678'"
                                :required="field.required"
                                :aria-invalid="form.errors[field.id] ? 'true' : undefined"
                                :aria-describedby="form.errors[field.id] ? `${field.id}-error` : undefined"
                                class="input"
                            />
                            <p v-if="form.errors[field.id]" :id="`${field.id}-error`" class="field-error">{{ form.errors[field.id] }}</p>
                        </div>

                        <div class="field sm:col-span-2">
                            <label for="message" class="field-label">{{ t('common.message') }}<span class="text-danger" aria-hidden="true"> *</span></label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                name="message"
                                rows="6"
                                :placeholder="t('contact.messagePlaceholder')"
                                required
                                :aria-invalid="form.errors.message ? 'true' : undefined"
                                :aria-describedby="form.errors.message ? 'message-error' : undefined"
                                class="input"
                            ></textarea>
                            <p v-if="form.errors.message" id="message-error" class="field-error">{{ form.errors.message }}</p>
                        </div>

                        <div class="flex flex-col gap-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="field-help">{{ t('contact.privacy') }}</p>
                            <button type="submit" :disabled="form.processing" class="btn btn-primary btn-lg">
                                <PhPaperPlaneTilt :size="18" weight="bold" aria-hidden="true" />
                                {{ form.processing ? t('common.submitting') : t('contact.send') }}
                            </button>
                        </div>

                        <div aria-live="polite" class="sm:col-span-2">
                            <p v-if="showSuccess" class="alert alert-success" role="status">
                                <PhCheckCircle :size="20" weight="fill" class="shrink-0 text-success" aria-hidden="true" />
                                {{ t('contact.success') }}
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>
