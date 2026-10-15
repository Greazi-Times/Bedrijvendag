<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    PhArrowCounterClockwise,
    PhArrowSquareOut,
    PhCheckCircle,
    PhCircleNotch,
    PhClockCounterClockwise,
    PhMagnifyingGlass,
    PhPaperPlaneTilt,
    PhPencilSimple,
    PhPlus,
    PhUploadSimple,
    PhWarningCircle,
    PhX,
} from '@phosphor-icons/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import PageIntro from '@/components/site/PageIntro.vue';
import RichTextEditor from '@/components/site/RichTextEditor.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import { useTranslations } from '@/i18n';
import { formatDate } from '@/lib/date';
import { focusFirstError } from '@/lib/forms';

type Option = {
    id: number;
    name: string;
};

type SubmissionResult = 'created' | 'updated' | 'duplicate';

const props = defineProps<{
    company: {
        name: string;
        logo_url?: string | null;
        website_url?: string | null;
        description?: string | null;
        education_ids: number[];
        sector_ids: number[];
        new_sector_names?: string[];
    };
    options: {
        educations: Option[];
        sectors: Option[];
    };
    pendingSubmission?: {
        submitted_at?: string | null;
    } | null;
    latestSubmission?: {
        status: 'pending' | 'approved' | 'rejected';
        submitted_at?: string | null;
        reviewed_at?: string | null;
    } | null;
    submissionResult?: SubmissionResult | null;
    submitUrl: string;
}>();
const { dateLocale, t } = useTranslations();

const logoPreview = ref<string | null>(props.company.logo_url ?? null);
const logoInput = ref<HTMLInputElement | null>(null);
const descriptionMaxLength = 5000;
const sectorSearch = ref('');
const newSectorName = ref('');

const form = useForm({
    contact_name: '',
    contact_email: '',
    name: props.company.name ?? '',
    logo: null as File | null,
    website_url: props.company.website_url ?? '',
    description: props.company.description ?? '',
    education_ids: [...(props.company.education_ids ?? [])],
    sector_ids: [...(props.company.sector_ids ?? [])],
    new_sector_names: [...(props.company.new_sector_names ?? [])],
});

/*
 * Change detection. The profile fields are reduced to a comparable snapshot
 * (trimmed text, sorted ids, case-insensitive proposed sectors, the selected
 * file's identity) and compared with the snapshot taken when the page loaded
 * or the last submission was received. Contact details describe who sends
 * the change, not the profile, so they never count as a change on their own.
 */
function snapshot(logo: File | null = form.logo): string {
    const ids = (values: number[]) => [...values].sort((a, b) => a - b);

    return JSON.stringify({
        name: form.name.trim(),
        website_url: form.website_url.trim(),
        description: form.description,
        education_ids: ids(form.education_ids),
        sector_ids: ids(form.sector_ids),
        new_sector_names: form.new_sector_names.map((name) => name.trim().toLowerCase()).sort(),
        logo: logo ? [logo.name, logo.size, logo.lastModified] : null,
    });
}

const cleanSnapshot = ref(snapshot());
const isDirty = computed(() => snapshot() !== cleanSnapshot.value);

// TipTap writes the stored description slightly differently (attribute order, <br>);
// take its version as the unchanged value so an untouched editor is not a change.
function onEditorReady(html: string) {
    const wasDirty = isDirty.value;
    form.description = html;
    if (!wasDirty) cleanSnapshot.value = snapshot();
}

/*
 * Submission state. `submitting` is set synchronously on the first click, before
 * Inertia reports `processing`, so a double click can never send a second request.
 */
const submitting = ref(false);
const result = ref<SubmissionResult | null>(null);
const errorMessage = ref<string | null>(null);

type SubmitState = 'unchanged' | 'modified' | 'submitting' | 'success' | 'error';

const state = computed<SubmitState>(() => {
    if (submitting.value) return 'submitting';
    if (errorMessage.value) return 'error';
    if (isDirty.value) return 'modified';
    if (result.value) return 'success';
    return 'unchanged';
});

const canSubmit = computed(() => isDirty.value && !submitting.value);

const statusText = computed(() => {
    switch (state.value) {
        case 'submitting':
            return t('companyProfile.stateSubmitting');
        case 'error':
            return t('companyProfile.errorTitle');
        case 'modified':
            return t('companyProfile.stateModified');
        case 'success':
            return result.value === 'duplicate' ? t('companyProfile.duplicateTitle') : t('companyProfile.successTitle');
        default:
            return t('companyProfile.stateUnchanged');
    }
});

const liveMessage = computed(() => {
    if (state.value === 'error') return '';
    if (state.value === 'success') return `${statusText.value}. ${result.value === 'duplicate' ? t('companyProfile.duplicateBody') : t('companyProfile.successBody')}`;
    return state.value === 'submitting' ? statusText.value : '';
});

const buttonLabel = computed(() => {
    if (state.value === 'submitting') return t('companyProfile.submitting');
    if (state.value === 'success') return t('companyProfile.received');
    return t('companyProfile.submit');
});

function formatDateTime(value?: string | null) {
    if (!value) return null;
    const date = formatDate(value, dateLocale.value);
    const time = formatDate(value, dateLocale.value, { hour: '2-digit', minute: '2-digit' });
    return date && time ? t('companyProfile.dateAt', { date, time }) : null;
}

const lastSubmittedAt = computed(() => formatDateTime(props.latestSubmission?.submitted_at));

const latestStatus = computed(() => {
    const status = props.latestSubmission?.status;
    if (status === 'approved') return t('companyProfile.statusApproved');
    if (status === 'rejected') return t('companyProfile.statusRejected');
    return t('companyProfile.statusPending');
});

const filteredSectors = computed(() => {
    const query = sectorSearch.value.trim().toLowerCase();

    if (!query) return props.options.sectors;

    return props.options.sectors.filter((sector) => sector.name.toLowerCase().includes(query));
});

const canAddNewSector = computed(() => {
    const name = normalizedSectorName(newSectorName.value || sectorSearch.value);

    if (!name) return false;

    const key = name.toLowerCase();
    const alreadyExists = props.options.sectors.some((sector) => sector.name.trim().toLowerCase() === key);
    const alreadyProposed = form.new_sector_names.some((sectorName) => sectorName.trim().toLowerCase() === key);

    return !alreadyExists && !alreadyProposed && form.new_sector_names.length < 10;
});

const descriptionLength = ref(0);

const sectorError = computed(() => {
    return form.errors.sector_ids || form.errors.new_sector_names || Object.entries(form.errors).find(([key]) => key.startsWith('new_sector_names.'))?.[1];
});

function toggleValue(values: number[], id: number) {
    const index = values.indexOf(id);
    if (index >= 0) values.splice(index, 1);
    else values.push(id);
}

function normalizedSectorName(value: string) {
    return value.replace(/\s+/g, ' ').trim();
}

function addNewSector() {
    const name = normalizedSectorName(newSectorName.value || sectorSearch.value);

    if (!canAddNewSector.value) return;

    form.new_sector_names.push(name);
    newSectorName.value = '';
    sectorSearch.value = '';
}

function removeNewSector(name: string) {
    form.new_sector_names = form.new_sector_names.filter((sectorName) => sectorName !== name);
}

function setLogoPreview(url: string | null) {
    if (logoPreview.value?.startsWith('blob:')) URL.revokeObjectURL(logoPreview.value);
    logoPreview.value = url;
}

function handleLogoChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    // Cancelling the file dialog keeps an earlier choice.
    if (!file) return;

    form.logo = file;
    setLogoPreview(URL.createObjectURL(file));
}

function clearLogo() {
    form.logo = null;
    if (logoInput.value) logoInput.value.value = '';
    setLogoPreview(props.company.logo_url ?? null);
}

function errorForStatus(status: number) {
    if (status === 419) return t('companyProfile.errorSession');
    if (status === 429) return t('companyProfile.errorThrottled');
    if (status === 404 || status === 410) return t('companyProfile.errorLink');
    return t('companyProfile.errorServer');
}

function submit() {
    if (!canSubmit.value) return;

    submitting.value = true;
    errorMessage.value = null;
    result.value = null;

    // What is being sent becomes the unchanged state once received; edits made
    // while the request is in flight stay marked as changes. The sent logo is
    // part of the proposal after that, so the clean state has no file selected.
    const sentLogo = form.logo;
    const sentSnapshot = snapshot(null);

    // Responses that are not Inertia pages (expired session, throttling, server
    // errors) and network failures would otherwise open Inertia's error modal or
    // fail silently. Handle them here, for this request only, and keep the input.
    let sessionExpired = false;
    const stopListening = [
        router.on('invalid', (event) => {
            event.preventDefault();
            sessionExpired = event.detail.response.status === 419;
            errorMessage.value = errorForStatus(event.detail.response.status);
        }),
        router.on('exception', (event) => {
            event.preventDefault();
            errorMessage.value = t('companyProfile.errorNetwork');
        }),
    ];

    form.post(props.submitUrl, {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onError: (errors) => {
            errorMessage.value = errors.form ?? t('companyProfile.errorValidation');
            focusFirstError();
        },
        onSuccess: (page) => {
            // Only reached when the server answered with the page again, i.e. it confirmed receipt.
            result.value = (page.props.submissionResult as SubmissionResult | null) ?? 'created';
            if (form.logo === sentLogo) clearLogo();
            cleanSnapshot.value = sentSnapshot;
        },
        onFinish: () => {
            stopListening.forEach((stop) => stop());
            submitting.value = false;
            // A 419 means the session (and CSRF token) expired. Reload the page props only,
            // which refreshes the token and keeps everything that was typed; the user retries.
            if (sessionExpired) router.reload({ only: ['latestSubmission'] });
        },
    });
}

/*
 * Unsaved changes. The browser shows its own dialog on reload, close and
 * external navigation; links inside the site go through Inertia and get the
 * native confirm. Nothing is shown without changes or while submitting.
 */
function warnBeforeUnload(event: BeforeUnloadEvent) {
    if (!isDirty.value || submitting.value) return;
    event.preventDefault();
    event.returnValue = '';
}

let stopNavigationGuard: (() => void) | null = null;

onMounted(() => {
    window.addEventListener('beforeunload', warnBeforeUnload);
    stopNavigationGuard = router.on('before', (event) => {
        const visit = event.detail.visit;
        if (visit.method !== 'get' || visit.only.length > 0 || !isDirty.value) return;
        if (!window.confirm(t('companyProfile.leaveWarning'))) event.preventDefault();
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', warnBeforeUnload);
    stopNavigationGuard?.();
    setLogoPreview(null);
});
</script>

<template>
    <Head :title="t('companyProfile.head')" />

    <SiteLayout>
        <PageIntro :eyebrow="t('companyProfile.eyebrow')" :title="t('companyProfile.title')" :lead="t('companyProfile.intro')" />

        <section class="site-container pt-14 pb-24 md:pt-20 md:pb-32">
            <div class="mx-auto max-w-3xl">
                <!-- Last submission, from the server, so it survives a reload. -->
                <div
                    v-if="latestSubmission"
                    class="alert mb-8"
                    :class="latestSubmission.status === 'rejected' ? 'bg-surface ring-1 ring-hairline ring-inset' : 'alert-success'"
                    data-test="latest-submission"
                >
                    <PhCheckCircle v-if="latestSubmission.status !== 'rejected'" :size="20" weight="fill" class="mt-0.5 shrink-0 text-success" aria-hidden="true" />
                    <PhClockCounterClockwise v-else :size="20" class="mt-0.5 shrink-0 text-ink-muted" aria-hidden="true" />
                    <div class="grid gap-0.5">
                        <p v-if="lastSubmittedAt" class="font-medium text-ink">{{ t('companyProfile.lastSubmitted', { date: lastSubmittedAt }) }}</p>
                        <p>{{ t('companyProfile.statusLabel', { status: latestStatus }) }}</p>
                        <p v-if="pendingSubmission" class="t-small mt-1">{{ t('companyProfile.pendingFormNote') }}</p>
                    </div>
                </div>

                <form class="card divide-y divide-hairline" novalidate @submit.prevent="submit">
                    <!-- Contact -->
                    <fieldset class="grid gap-5 p-6 sm:p-10">
                        <legend class="contents">
                            <span class="t-h4 block text-ink">{{ t('companyProfile.sectionContact') }}</span>
                        </legend>
                        <p class="t-small -mt-3">{{ t('companyProfile.sectionContactHelp') }}</p>

                        <div class="field">
                            <label for="contact_name" class="field-label">{{ t('companyProfile.contactPerson') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <input
                                id="contact_name"
                                v-model="form.contact_name"
                                type="text"
                                autocomplete="name"
                                required
                                class="input"
                                :aria-invalid="form.errors.contact_name ? 'true' : undefined"
                                :aria-describedby="form.errors.contact_name ? 'contact_name-error' : undefined"
                            />
                            <p v-if="form.errors.contact_name" id="contact_name-error" class="field-error">{{ form.errors.contact_name }}</p>
                        </div>

                        <div class="field">
                            <label for="contact_email" class="field-label">{{ t('companyProfile.contactEmail') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <input
                                id="contact_email"
                                v-model="form.contact_email"
                                type="email"
                                autocomplete="email"
                                spellcheck="false"
                                required
                                class="input"
                                :aria-invalid="form.errors.contact_email ? 'true' : undefined"
                                :aria-describedby="form.errors.contact_email ? 'contact_email-error' : undefined"
                            />
                            <p v-if="form.errors.contact_email" id="contact_email-error" class="field-error">{{ form.errors.contact_email }}</p>
                        </div>
                    </fieldset>

                    <!-- Company -->
                    <fieldset class="grid gap-5 p-6 sm:p-10">
                        <legend class="contents">
                            <span class="t-h4 block text-ink">{{ t('companyProfile.sectionCompany') }}</span>
                        </legend>

                        <div class="field">
                            <label for="name" class="field-label">{{ t('companyProfile.companyName') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                autocomplete="organization"
                                required
                                class="input"
                                :aria-invalid="form.errors.name ? 'true' : undefined"
                                :aria-describedby="form.errors.name ? 'name-error' : undefined"
                            />
                            <p v-if="form.errors.name" id="name-error" class="field-error">{{ form.errors.name }}</p>
                        </div>

                        <div class="field">
                            <label for="website_url" class="field-label">{{ t('common.website') }}</label>
                            <input
                                id="website_url"
                                v-model="form.website_url"
                                type="url"
                                inputmode="url"
                                spellcheck="false"
                                placeholder="https://example.com"
                                class="input"
                                :aria-invalid="form.errors.website_url ? 'true' : undefined"
                                :aria-describedby="form.errors.website_url ? 'website_url-error' : undefined"
                            />
                            <p v-if="form.errors.website_url" id="website_url-error" class="field-error">{{ form.errors.website_url }}</p>
                            <a
                                v-if="company.website_url"
                                :href="company.website_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="t-link inline-flex items-center gap-1.5 self-start text-sm"
                            >
                                {{ t('companyProfile.openWebsite') }}
                                <PhArrowSquareOut :size="14" aria-hidden="true" />
                            </a>
                        </div>

                        <div class="field">
                            <span class="field-label">{{ t('companyProfile.logo') }}</span>
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <div class="flex h-24 w-40 shrink-0 items-center justify-center rounded-[var(--radius-input)] bg-white p-3 ring-1 ring-hairline">
                                    <img v-if="logoPreview" :src="logoPreview" :alt="form.name" class="max-h-full max-w-full object-contain" />
                                    <span v-else class="text-sm text-[#545a68]">{{ t('common.noLogo') }}</span>
                                </div>
                                <div class="grid justify-items-start gap-2">
                                    <label for="logo" class="btn btn-secondary cursor-pointer focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand">
                                        <PhUploadSimple :size="16" aria-hidden="true" />
                                        {{ t('companyProfile.uploadLogo') }}
                                        <input id="logo" ref="logoInput" type="file" accept="image/*" class="sr-only" @change="handleLogoChange" />
                                    </label>
                                    <p v-if="form.logo" class="field-help flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span class="break-all">{{ t('companyProfile.newLogoSelected', { name: form.logo.name }) }}</span>
                                        <button type="button" class="t-link inline-flex items-center gap-1" @click="clearLogo">
                                            <PhArrowCounterClockwise :size="14" aria-hidden="true" />
                                            {{ t('companyProfile.undoLogo') }}
                                        </button>
                                    </p>
                                    <p v-if="form.errors.logo" class="field-error">{{ form.errors.logo }}</p>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Description -->
                    <div class="grid gap-3 p-6 sm:p-10">
                        <h2 id="description-label" class="t-h4 text-ink">{{ t('common.description') }}</h2>
                        <RichTextEditor
                            id="description"
                            v-model="form.description"
                            :placeholder="t('companyProfile.descriptionPlaceholder')"
                            labelled-by="description-label"
                            described-by="description-help"
                            :invalid="Boolean(form.errors.description)"
                            @text-length="descriptionLength = $event"
                            @ready="onEditorReady"
                        />
                        <div id="description-help" class="flex items-start justify-between gap-4 text-xs text-ink-muted">
                            <p>{{ t('companyProfile.editorHelp') }}</p>
                            <p class="shrink-0 tabular-nums" :class="{ 'text-danger': descriptionLength > descriptionMaxLength }">
                                {{ descriptionLength }} / {{ descriptionMaxLength }}
                            </p>
                        </div>
                        <p v-if="form.errors.description" class="field-error">{{ form.errors.description }}</p>
                    </div>

                    <!-- Educations -->
                    <fieldset class="grid gap-4 p-6 sm:p-10">
                        <legend class="contents">
                            <span class="t-h4 block text-ink">{{ t('common.educations') }}</span>
                        </legend>
                        <p class="t-small -mt-2">{{ t('companyProfile.sectionEducationsHelp') }}</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="education in options.educations"
                                :key="education.id"
                                class="flex cursor-pointer items-start gap-3 rounded-[var(--radius-input)] px-3 py-2.5 text-sm text-ink ring-1 ring-hairline transition-colors hover:bg-surface has-[:checked]:bg-surface has-[:checked]:ring-brand/40"
                            >
                                <input
                                    type="checkbox"
                                    class="mt-0.5 size-4 shrink-0 accent-[var(--site-brand)]"
                                    :checked="form.education_ids.includes(education.id)"
                                    @change="toggleValue(form.education_ids, education.id)"
                                />
                                <span>{{ education.name }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.education_ids" class="field-error">{{ form.errors.education_ids }}</p>
                    </fieldset>

                    <!-- Sectors -->
                    <fieldset class="grid gap-4 p-6 sm:p-10">
                        <legend class="contents">
                            <span class="t-h4 block text-ink">{{ t('common.sectors') }}</span>
                        </legend>
                        <div class="relative">
                            <PhMagnifyingGlass :size="16" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-ink-muted" aria-hidden="true" />
                            <label for="sector_search" class="sr-only">{{ t('companyProfile.searchSector') }}</label>
                            <input id="sector_search" v-model="sectorSearch" type="search" :placeholder="t('companyProfile.searchSector')" class="input pl-10" />
                        </div>

                        <div class="max-h-64 overflow-y-auto rounded-[var(--radius-input)] bg-surface p-3 ring-1 ring-hairline">
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="sector in filteredSectors"
                                    :key="sector.id"
                                    class="inline-flex max-w-full cursor-pointer items-center gap-2.5 rounded-[var(--radius-input)] bg-canvas px-3 py-2 text-sm text-ink ring-1 ring-hairline has-[:checked]:ring-brand/40 dark:bg-surface-2"
                                >
                                    <input
                                        type="checkbox"
                                        class="size-4 shrink-0 accent-[var(--site-brand)]"
                                        :checked="form.sector_ids.includes(sector.id)"
                                        @change="toggleValue(form.sector_ids, sector.id)"
                                    />
                                    <span>{{ sector.name }}</span>
                                </label>
                            </div>
                            <p v-if="!filteredSectors.length" class="t-small">{{ t('companyProfile.noSector') }}</p>
                        </div>

                        <div class="field">
                            <label for="new_sector_name" class="field-label">{{ t('companyProfile.newSector') }}</label>
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <input
                                    id="new_sector_name"
                                    v-model="newSectorName"
                                    type="text"
                                    maxlength="80"
                                    :placeholder="t('companyProfile.newSectorExample')"
                                    class="input min-w-0 flex-1"
                                    aria-describedby="new_sector_help"
                                    @keydown.enter.prevent="addNewSector"
                                />
                                <button type="button" :disabled="!canAddNewSector" class="btn btn-secondary" @click="addNewSector">
                                    <PhPlus :size="16" aria-hidden="true" />
                                    {{ t('companyProfile.add') }}
                                </button>
                            </div>
                            <p id="new_sector_help" class="field-help">{{ t('companyProfile.newSectorHelp') }}</p>

                            <ul v-if="form.new_sector_names.length" class="mt-1 flex flex-wrap gap-2">
                                <li v-for="name in form.new_sector_names" :key="name" class="chip chip-brand gap-1.5 pr-1">
                                    {{ name }}
                                    <button
                                        type="button"
                                        :aria-label="t('companyProfile.remove', { name })"
                                        class="inline-flex size-5 items-center justify-center rounded-[var(--radius-chip)] hover:bg-brand/15"
                                        @click="removeNewSector(name)"
                                    >
                                        <PhX :size="12" weight="bold" aria-hidden="true" />
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <p v-if="sectorError" class="field-error">{{ sectorError }}</p>
                    </fieldset>

                    <!--
                        Submit bar. The form is long, so this one bar sticks to the bottom of the
                        viewport while the form is in view and settles at the end of the card.
                    -->
                    <div
                        class="sticky bottom-0 z-10 rounded-b-[var(--radius-card)] bg-canvas px-6 pt-4 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-[0_-1px_0_var(--site-hairline),0_-8px_24px_-16px_rgb(0_0_0/0.25)] sm:px-10 sm:py-5 dark:bg-surface"
                        :data-state="state"
                    >
                        <!-- Errors announce themselves (role="alert"); the other states are read out from here. -->
                        <p class="sr-only" role="status" aria-live="polite">{{ liveMessage }}</p>

                        <div v-if="state === 'success'" class="alert alert-success mb-4" data-test="submission-success">
                            <PhCheckCircle :size="20" weight="fill" class="mt-0.5 shrink-0 text-success" aria-hidden="true" />
                            <div>
                                <p class="font-medium text-ink">{{ statusText }}</p>
                                <p>{{ result === 'duplicate' ? t('companyProfile.duplicateBody') : t('companyProfile.successBody') }}</p>
                            </div>
                        </div>

                        <div v-else-if="state === 'error' && errorMessage" class="alert alert-error mb-4" role="alert" data-test="submission-error">
                            <PhWarningCircle :size="20" weight="fill" class="mt-0.5 shrink-0 text-danger" aria-hidden="true" />
                            <div>
                                <p class="font-medium text-ink">{{ t('companyProfile.errorTitle') }}</p>
                                <p>{{ errorMessage }}</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                            <div class="flex min-w-0 items-start gap-2.5">
                                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center" aria-hidden="true">
                                    <PhCircleNotch v-if="state === 'submitting'" :size="18" class="text-ink-muted motion-safe:animate-spin" />
                                    <PhWarningCircle v-else-if="state === 'error'" :size="18" weight="fill" class="text-danger" />
                                    <PhPencilSimple v-else-if="state === 'modified'" :size="18" weight="bold" class="text-brand-ink" />
                                    <PhCheckCircle v-else :size="18" class="text-ink-muted" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ink" data-test="submission-status">
                                        <!-- After success the alert above carries the confirmation; this line only says nothing is left to send. -->
                                        {{ state === 'success' ? t('companyProfile.stateUnchanged') : statusText }}
                                    </p>
                                    <p class="t-small max-sm:hidden">{{ t('companyProfile.approval') }}</p>
                                </div>
                            </div>
                            <button
                                type="submit"
                                :disabled="!canSubmit"
                                :aria-busy="state === 'submitting' ? 'true' : undefined"
                                class="btn btn-primary btn-lg shrink-0 sm:min-w-[16.5rem]"
                                data-test="submit-changes"
                            >
                                <PhCircleNotch v-if="state === 'submitting'" :size="18" class="motion-safe:animate-spin" aria-hidden="true" />
                                <PhCheckCircle v-else-if="state === 'success'" :size="18" weight="fill" aria-hidden="true" />
                                <PhPaperPlaneTilt v-else :size="18" aria-hidden="true" />
                                {{ buttonLabel }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </SiteLayout>
</template>
