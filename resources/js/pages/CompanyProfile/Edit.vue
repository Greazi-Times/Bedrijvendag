<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { PhArrowSquareOut, PhCheckCircle, PhMagnifyingGlass, PhPaperPlaneTilt, PhPlus, PhUploadSimple, PhX } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';

import PageIntro from '@/components/site/PageIntro.vue';
import RichTextEditor from '@/components/site/RichTextEditor.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import { useTranslations } from '@/i18n';
import { focusFirstError } from '@/lib/forms';

type Option = {
    id: number;
    name: string;
};

const props = defineProps<{
    company: {
        name: string;
        logo_url?: string | null;
        website_url?: string | null;
        description?: string | null;
        education_ids: number[];
        sector_ids: number[];
    };
    options: {
        educations: Option[];
        sectors: Option[];
    };
    pendingSubmission?: {
        submitted_at?: string | null;
    } | null;
    submitUrl: string;
}>();
const { dateLocale, t } = useTranslations();

const logoPreview = ref<string | null>(props.company.logo_url ?? null);
const saved = ref(false);
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
    new_sector_names: [] as string[],
});

const submittedAt = computed(() => {
    if (!props.pendingSubmission?.submitted_at) return null;

    const date = new Date(props.pendingSubmission.submitted_at);
    if (Number.isNaN(date.getTime())) return null;

    return date.toLocaleDateString(dateLocale.value, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
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

function handleLogoChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    form.logo = file;

    if (!file) {
        logoPreview.value = props.company.logo_url ?? null;
        return;
    }

    logoPreview.value = URL.createObjectURL(file);
}

function submit() {
    form.post(props.submitUrl, {
        preserveScroll: true,
        forceFormData: true,
        onError: focusFirstError,
        onSuccess: () => {
            saved.value = true;
            form.logo = null;
        },
    });
}
</script>

<template>
    <Head :title="t('companyProfile.head')" />

    <SiteLayout>
        <PageIntro :eyebrow="t('companyProfile.eyebrow')" :title="t('companyProfile.title')" :lead="t('companyProfile.intro')" />

        <section class="site-container pt-14 pb-24 md:pt-20 md:pb-32">
            <div class="mx-auto max-w-3xl">
                <div v-if="pendingSubmission || saved" class="alert alert-success mb-8" role="status" aria-live="polite">
                    <PhCheckCircle :size="20" weight="fill" class="shrink-0 text-success" aria-hidden="true" />
                    <p>{{ t('companyProfile.received', { date: submittedAt ? t('companyProfile.onDate', { date: submittedAt }) : '' }) }}</p>
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
                                <div>
                                    <label for="logo" class="btn btn-secondary cursor-pointer focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand">
                                        <PhUploadSimple :size="16" aria-hidden="true" />
                                        {{ t('companyProfile.uploadLogo') }}
                                        <input id="logo" type="file" accept="image/*" class="sr-only" @change="handleLogoChange" />
                                    </label>
                                    <p v-if="form.errors.logo" class="field-error mt-2">{{ form.errors.logo }}</p>
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

                    <!-- Submit -->
                    <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-10">
                        <p class="t-small max-w-md">{{ t('companyProfile.approval') }}</p>
                        <button type="submit" :disabled="form.processing" class="btn btn-primary btn-lg shrink-0">
                            <PhPaperPlaneTilt :size="18" aria-hidden="true" />
                            {{ form.processing ? t('common.submitting') : t('companyProfile.sendReview') }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </SiteLayout>
</template>
