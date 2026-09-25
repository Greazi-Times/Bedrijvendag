<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Building2, CheckCircle2, Plus, PlusCircle, Search, Send, Upload, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { useTranslations } from '@/i18n';

type CompanyOption = {
    id: number;
    name: string;
    website_url?: string | null;
};

type Option = {
    id: number;
    name: string;
};

const props = defineProps<{
    companies: CompanyOption[];
    options: {
        educations: Option[];
        sectors: Option[];
    };
    submitUrl: string;
}>();
const { t } = useTranslations();

const mode = ref<'existing' | 'new'>('existing');
const query = ref('');
const saved = ref(false);
const logoPreview = ref<string | null>(null);
const sectorSearch = ref('');
const newSectorName = ref('');

const form = useForm({
    type: 'existing',
    company_id: null as number | null,
    company_name: '',
    website_url: '',
    logo: null as File | null,
    description: '',
    education_ids: [] as number[],
    sector_ids: [] as number[],
    new_sector_names: [] as string[],
    contact_name: '',
    contact_email: '',
    message: '',
});

const filteredCompanies = computed(() => {
    const value = query.value.trim().toLowerCase();

    if (!value) return props.companies.slice(0, 12);

    return props.companies.filter((company) => [company.name, company.website_url ?? ''].join(' ').toLowerCase().includes(value)).slice(0, 20);
});

const selectedCompany = computed(() => props.companies.find((company) => company.id === form.company_id) ?? null);

const filteredSectors = computed(() => {
    const value = sectorSearch.value.trim().toLowerCase();

    if (!value) return props.options.sectors;

    return props.options.sectors.filter((sector) => sector.name.toLowerCase().includes(value));
});

const canAddNewSector = computed(() => {
    const name = normalizedSectorName(newSectorName.value || sectorSearch.value);

    if (!name || form.new_sector_names.length >= 10) return false;

    const key = name.toLowerCase();

    return !props.options.sectors.some((sector) => sector.name.trim().toLowerCase() === key) && !form.new_sector_names.some((sector) => sector.trim().toLowerCase() === key);
});

const sectorError = computed(() => {
    return form.errors.sector_ids || form.errors.new_sector_names || Object.entries(form.errors).find(([key]) => key.startsWith('new_sector_names.'))?.[1];
});

watch(mode, (value) => {
    form.type = value;
    saved.value = false;

    if (value === 'existing') {
        form.company_name = '';
        form.website_url = '';
        form.logo = null;
        form.description = '';
        form.education_ids = [];
        form.sector_ids = [];
        form.new_sector_names = [];
        logoPreview.value = null;
        sectorSearch.value = '';
        newSectorName.value = '';
    } else {
        form.company_id = null;
        query.value = '';
    }
});

function selectCompany(company: CompanyOption) {
    form.company_id = company.id;
    query.value = company.name;
}

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
    form.new_sector_names = form.new_sector_names.filter((sector) => sector !== name);
}

function handleLogoChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    form.logo = file;
    logoPreview.value = file ? URL.createObjectURL(file) : null;
}

function submit() {
    form.type = mode.value;

    form.post(props.submitUrl, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            saved.value = true;
            form.reset();
            query.value = '';
            logoPreview.value = null;
            sectorSearch.value = '';
            newSectorName.value = '';
        },
    });
}
</script>

<template>
    <Head :title="t('companyAccess.head')" />

    <AppHeader class="sticky top-0 z-50" />

    <main class="brand-hero min-h-screen px-6 py-12 lg:px-16">
        <div class="mx-auto max-w-5xl">
            <div class="max-w-3xl">
                <p class="brand-eyebrow">{{ t('companyAccess.eyebrow') }}</p>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">{{ t('companyAccess.title') }}</h1>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">{{ t('companyAccess.intro') }}</p>
            </div>

            <div v-if="saved" class="mt-8 rounded-xl bg-emerald-500/15 p-4 text-sm text-emerald-900 ring-1 ring-emerald-500/25" role="status" aria-live="polite">
                <div class="flex items-start gap-3">
                    <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                    <p>{{ t('companyAccess.success') }}</p>
                </div>
            </div>

            <form class="brand-card mt-10 rounded-2xl p-6 sm:p-8" @submit.prevent="submit">
                <div class="grid gap-3 sm:grid-cols-2">
                    <button
                        type="button"
                        class="flex items-center gap-3 rounded-xl px-4 py-3 text-left text-sm font-semibold ring-1 transition"
                        :class="mode === 'existing' ? 'bg-primary text-primary-foreground ring-primary' : 'bg-background text-foreground ring-border hover:bg-secondary/10'"
                        @click="mode = 'existing'"
                    >
                        <Building2 class="h-5 w-5 shrink-0" />
                        {{ t('companyAccess.existing') }}
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-3 rounded-xl px-4 py-3 text-left text-sm font-semibold ring-1 transition"
                        :class="mode === 'new' ? 'bg-primary text-primary-foreground ring-primary' : 'bg-background text-foreground ring-border hover:bg-secondary/10'"
                        @click="mode = 'new'"
                    >
                        <PlusCircle class="h-5 w-5 shrink-0" />
                        {{ t('companyAccess.new') }}
                    </button>
                </div>

                <div class="mt-8 grid gap-6 lg:grid-cols-2">
                    <template v-if="mode === 'existing'">
                        <div class="lg:col-span-2">
                            <label for="company_search" class="mb-2 block text-sm font-semibold text-foreground">{{ t('companyAccess.searchCompany') }}</label>
                            <div class="relative">
                                <Search class="pointer-events-none absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <input
                                    id="company_search"
                                    v-model="query"
                                    type="search"
                                    autocomplete="off"
                                    :placeholder="t('companyAccess.searchPlaceholder')"
                                    class="brand-input w-full rounded-xl py-3 pr-4 pl-11 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                                />
                            </div>

                            <div class="mt-3 max-h-72 overflow-y-auto rounded-xl bg-background/70 ring-1 ring-border">
                                <button
                                    v-for="company in filteredCompanies"
                                    :key="company.id"
                                    type="button"
                                    class="block w-full border-b border-border px-4 py-3 text-left text-sm transition last:border-b-0 hover:bg-secondary/10"
                                    :class="form.company_id === company.id ? 'bg-primary/10 text-primary' : 'text-foreground'"
                                    @click="selectCompany(company)"
                                >
                                    <span class="font-semibold">{{ company.name }}</span>
                                    <span v-if="company.website_url" class="mt-1 block text-xs text-muted-foreground">{{ company.website_url }}</span>
                                </button>
                                <p v-if="!filteredCompanies.length" class="px-4 py-5 text-sm text-muted-foreground">{{ t('companyAccess.notFound') }}</p>
                            </div>

                            <p v-if="selectedCompany" class="mt-2 text-xs text-muted-foreground">{{ t('companyAccess.selectedCompany', { name: selectedCompany.name }) }}</p>
                            <p v-if="form.errors.company_id" class="mt-2 text-sm text-destructive">{{ form.errors.company_id }}</p>
                        </div>
                    </template>

                    <template v-else>
                        <div class="lg:col-span-2">
                            <h2 class="text-xl font-semibold text-foreground">{{ t('companyAccess.newCompanyTitle') }}</h2>
                            <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ t('companyAccess.newCompanyHelp') }}</p>
                        </div>

                        <div>
                            <label for="company_name" class="mb-2 block text-sm font-semibold text-foreground"
                                >{{ t('companyAccess.companyName') }} <span class="text-destructive">*</span></label
                            >
                            <input
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                required
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />
                            <p v-if="form.errors.company_name" class="mt-2 text-sm text-destructive">{{ form.errors.company_name }}</p>
                        </div>

                        <div>
                            <label for="website_url" class="mb-2 block text-sm font-semibold text-foreground">{{ t('common.website') }}</label>
                            <input
                                id="website_url"
                                v-model="form.website_url"
                                type="url"
                                placeholder="https://example.com"
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />
                            <p v-if="form.errors.website_url" class="mt-2 text-sm text-destructive">{{ form.errors.website_url }}</p>
                        </div>

                        <div class="lg:col-span-2">
                            <label for="description" class="mb-2 block text-sm font-semibold text-foreground"
                                >{{ t('common.description') }} <span class="text-destructive">*</span></label
                            >
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="6"
                                required
                                maxlength="5000"
                                :placeholder="t('companyProfile.descriptionPlaceholder')"
                                class="brand-input w-full rounded-xl p-4 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-2 text-sm text-destructive">{{ form.errors.description }}</p>
                        </div>

                        <div class="lg:col-span-2">
                            <p class="text-sm font-semibold text-foreground">{{ t('companyProfile.logo') }} <span class="text-destructive">*</span></p>
                            <div class="mt-3 flex flex-col gap-4 rounded-xl bg-background/50 p-4 ring-1 ring-border sm:flex-row sm:items-center">
                                <div class="flex h-32 w-full shrink-0 items-center justify-center rounded-xl bg-background p-4 ring-1 ring-border sm:w-48">
                                    <img v-if="logoPreview" :src="logoPreview" :alt="form.company_name" class="max-h-full max-w-full object-contain" />
                                    <span v-else class="text-sm text-muted-foreground">{{ t('common.noLogo') }}</span>
                                </div>
                                <div>
                                    <label
                                        for="logo"
                                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-background px-4 py-3 text-sm font-semibold text-foreground ring-1 ring-border transition hover:bg-secondary/10"
                                    >
                                        <Upload class="h-4 w-4" />
                                        {{ t('companyProfile.uploadLogo') }}
                                    </label>
                                    <input id="logo" type="file" accept="image/*" required class="sr-only" @change="handleLogoChange" />
                                    <p class="mt-2 text-xs text-muted-foreground">{{ t('companyAccess.logoHelp') }}</p>
                                    <p v-if="form.errors.logo" class="mt-2 text-sm text-destructive">{{ form.errors.logo }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-2">
                            <p class="text-sm font-semibold text-foreground">{{ t('common.educations') }} <span class="text-destructive">*</span></p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ t('companyAccess.educationsHelp') }}</p>
                            <div class="mt-3 grid gap-3 rounded-xl bg-background/40 p-4 ring-1 ring-border sm:grid-cols-2">
                                <label v-for="education in options.educations" :key="education.id" class="flex items-start gap-3 text-sm text-foreground">
                                    <input
                                        type="checkbox"
                                        class="mt-0.5 h-4 w-4 rounded border-border text-primary focus:ring-ring/40"
                                        :checked="form.education_ids.includes(education.id)"
                                        @change="toggleValue(form.education_ids, education.id)"
                                    />
                                    <span>{{ education.name }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.education_ids" class="mt-2 text-sm text-destructive">{{ form.errors.education_ids }}</p>
                        </div>

                        <div class="lg:col-span-2">
                            <p class="text-sm font-semibold text-foreground">{{ t('common.sectors') }}</p>
                            <div class="relative mt-3">
                                <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <input
                                    v-model="sectorSearch"
                                    type="search"
                                    :placeholder="t('companyProfile.searchSector')"
                                    class="brand-input w-full rounded-xl py-3 pr-4 pl-10 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                                />
                            </div>

                            <div class="mt-3 max-h-56 overflow-y-auto rounded-xl bg-background/40 p-3 ring-1 ring-border">
                                <div class="flex flex-wrap gap-3">
                                    <label
                                        v-for="sector in filteredSectors"
                                        :key="sector.id"
                                        class="inline-flex max-w-full items-center gap-3 rounded-lg bg-background/80 px-3 py-2 text-sm whitespace-nowrap text-foreground ring-1 ring-border"
                                    >
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 shrink-0 rounded border-border text-primary focus:ring-ring/40"
                                            :checked="form.sector_ids.includes(sector.id)"
                                            @change="toggleValue(form.sector_ids, sector.id)"
                                        />
                                        <span>{{ sector.name }}</span>
                                    </label>
                                </div>
                                <p v-if="!filteredSectors.length" class="text-sm text-muted-foreground">{{ t('companyProfile.noSector') }}</p>
                            </div>

                            <div class="mt-4 rounded-xl bg-background/60 p-4 ring-1 ring-border">
                                <label for="new_sector_name" class="text-sm font-semibold text-foreground">{{ t('companyProfile.newSector') }}</label>
                                <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                                    <input
                                        id="new_sector_name"
                                        v-model="newSectorName"
                                        type="text"
                                        maxlength="80"
                                        :placeholder="t('companyProfile.newSectorExample')"
                                        class="brand-input min-w-0 flex-1 rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                                        @keydown.enter.prevent="addNewSector"
                                    />
                                    <button
                                        type="button"
                                        :disabled="!canAddNewSector"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                                        @click="addNewSector"
                                    >
                                        <Plus class="h-4 w-4" />
                                        {{ t('companyProfile.add') }}
                                    </button>
                                </div>
                                <p class="mt-2 text-xs text-muted-foreground">{{ t('companyProfile.newSectorHelp') }}</p>

                                <div v-if="form.new_sector_names.length" class="mt-4 flex flex-wrap gap-2">
                                    <span
                                        v-for="name in form.new_sector_names"
                                        :key="name"
                                        class="inline-flex max-w-full items-center gap-2 rounded-lg bg-primary/10 px-3 py-2 text-sm font-semibold whitespace-nowrap text-primary ring-1 ring-primary/20"
                                    >
                                        {{ name }}
                                        <button
                                            type="button"
                                            :aria-label="t('companyProfile.remove', { name })"
                                            class="rounded-md p-0.5 transition hover:bg-primary/10"
                                            @click="removeNewSector(name)"
                                        >
                                            <X class="h-3.5 w-3.5" />
                                        </button>
                                    </span>
                                </div>
                            </div>
                            <p v-if="sectorError" class="mt-2 text-sm text-destructive">{{ sectorError }}</p>
                        </div>
                    </template>

                    <div>
                        <label for="contact_name" class="mb-2 block text-sm font-semibold text-foreground"
                            >{{ t('companyAccess.contactPerson') }} <span class="text-destructive">*</span></label
                        >
                        <input
                            id="contact_name"
                            v-model="form.contact_name"
                            type="text"
                            autocomplete="name"
                            class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                        />
                        <p v-if="form.errors.contact_name" class="mt-2 text-sm text-destructive">{{ form.errors.contact_name }}</p>
                    </div>

                    <div>
                        <label for="contact_email" class="mb-2 block text-sm font-semibold text-foreground"
                            >{{ t('companyAccess.businessEmail') }} <span class="text-destructive">*</span></label
                        >
                        <input
                            id="contact_email"
                            v-model="form.contact_email"
                            type="email"
                            autocomplete="email"
                            class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                        />
                        <p v-if="form.errors.contact_email" class="mt-2 text-sm text-destructive">{{ form.errors.contact_email }}</p>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="message" class="mb-2 block text-sm font-semibold text-foreground">{{ t('common.message') }}</label>
                        <textarea
                            id="message"
                            v-model="form.message"
                            rows="4"
                            class="brand-input w-full rounded-xl p-4 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                        ></textarea>
                        <p v-if="form.errors.message" class="mt-2 text-sm text-destructive">{{ form.errors.message }}</p>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm leading-relaxed text-muted-foreground">{{ t('companyAccess.privateLink') }}</p>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <Send class="h-4 w-4" />
                        {{ form.processing ? t('common.submitting') : t('companyAccess.sendRequest') }}
                    </button>
                </div>
            </form>
        </div>
    </main>

    <AppFooter />
</template>
