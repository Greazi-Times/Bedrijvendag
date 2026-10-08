<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { PhArrowUpRight, PhBuildings, PhFunnelSimple, PhMagnifyingGlass, PhPlus } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import ActiveFilterChips from '@/components/site/ActiveFilterChips.vue';
import CompanyDialog from '@/components/site/CompanyDialog.vue';
import EmptyState from '@/components/site/EmptyState.vue';
import FilterSheet from '@/components/site/FilterSheet.vue';
import PageIntro from '@/components/site/PageIntro.vue';
import SearchField from '@/components/site/SearchField.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import { useTranslations } from '@/i18n';
import { htmlToText } from '@/lib/sanitize';
import type { CompanyDialogData } from '@/types/site';

type EventDto = {
    id: number;
    title: string;
    date: string; // YYYY-MM-DD or ISO
};

type FilterOption = {
    id: number | string;
    name: string;
};

type CompanyDto = {
    id: number;
    name: string;
    logo_url?: string | null;
    website_url?: string | null;
    booth?: string | null;
    description?: string | null;

    // For filtering (names are easiest; controller can send these)
    educations?: string[] | null;
    sectors?: string[] | null;
};

const props = defineProps<{
    event: EventDto | null;
    companies: CompanyDto[];
    eventKind?: 'upcoming' | 'most-recent';

    // Optional: if you want to show filter chips from DB
    educations?: FilterOption[];
    sectors?: FilterOption[];
}>();
const { dateLocale, t } = useTranslations();

const educationsPreview = (company: CompanyDto) => (company.educations ?? []).slice(0, 2);

const q = ref('');
const selectedEducations = ref<string[]>([]);
const selectedSectors = ref<string[]>([]);

const isFilterOpen = ref(false);

const selectedCompany = ref<CompanyDto | null>(null);
const isCompanyOpen = ref(false);

const openCompany = (company: CompanyDto) => {
    selectedCompany.value = company;
    isCompanyOpen.value = true;
};

const companyDialogData = computed<CompanyDialogData | null>(() => {
    const c = selectedCompany.value;
    if (!c) return null;

    return {
        name: c.name,
        kind: t('common.company'),
        stand: c.booth,
        logoUrl: c.logo_url,
        description: c.description,
        educations: c.educations,
        sectors: c.sectors,
        websiteUrl: c.website_url,
    };
});

const eventDateLabel = computed(() => {
    const v = props.event?.date;
    if (!v) return '';

    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return v;

    return d.toLocaleDateString(dateLocale.value, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

const headerSubtitle = computed(() => {
    if (!props.event) return t('companies.noEdition');

    const kind = props.eventKind === 'upcoming' ? t('companies.upcomingEdition') : t('companies.recentEdition');
    const date = eventDateLabel.value;

    return t('companies.subtitle', { edition: kind, date: date ? t('companies.onDate', { date }) : '' });
});

const educationOptions = computed<string[]>(() => {
    if (props.educations?.length) return props.educations.map((e) => e.name);

    const set = new Set<string>();
    for (const c of props.companies) for (const n of c.educations ?? []) set.add(n);
    return Array.from(set).sort((a, b) => a.localeCompare(b, 'nl'));
});

const sectorOptions = computed<string[]>(() => {
    if (props.sectors?.length) return props.sectors.map((s) => s.name);

    const set = new Set<string>();
    for (const c of props.companies) for (const n of c.sectors ?? []) set.add(n);
    return Array.from(set).sort((a, b) => a.localeCompare(b, 'nl'));
});

const clearAll = () => {
    selectedEducations.value = [];
    selectedSectors.value = [];
};

const filteredCompanies = computed(() => {
    const query = q.value.trim().toLowerCase();
    const edu = selectedEducations.value;
    const sec = selectedSectors.value;

    return (props.companies ?? [])
        .filter((c) => {
            if (!query) return true;

            const haystack = [c.name, c.booth ?? '', c.website_url ?? '', c.description ?? '', ...(c.educations ?? []), ...(c.sectors ?? [])].join(' ').toLowerCase();

            return haystack.includes(query);
        })
        .filter((c) => {
            if (!edu.length) return true;
            const names = c.educations ?? [];
            return edu.some((x) => names.includes(x));
        })
        .filter((c) => {
            if (!sec.length) return true;
            const names = c.sectors ?? [];
            return sec.some((x) => names.includes(x));
        })
        .slice()
        .sort((a, b) => a.name.localeCompare(b.name, 'nl'));
});

const activeFilterCount = computed(() => selectedEducations.value.length + selectedSectors.value.length);

const scrollToNewsletter = () => {
    const el = document.getElementById('newsletter-email') as HTMLInputElement | null;
    if (!el) return;

    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    window.setTimeout(() => el.focus(), 250);
};
</script>

<template>
    <Head :title="t('companies.title')" />

    <SiteLayout>
        <PageIntro :eyebrow="t('companies.eyebrow')" :title="t('companies.title')" :lead="headerSubtitle" />

        <section class="site-container pt-10 pb-24 md:pt-14 md:pb-32">
            <div class="flex flex-col gap-4 border-b border-hairline pb-6 md:flex-row md:items-center">
                <div class="w-full md:max-w-md">
                    <SearchField id="company-search" v-model="q" :label="t('common.search')" :placeholder="t('companies.searchPlaceholder')" />
                </div>
                <div class="flex items-center justify-between gap-3 md:flex-1">
                    <button type="button" class="btn btn-secondary" @click="isFilterOpen = true">
                        <PhFunnelSimple :size="18" aria-hidden="true" />
                        {{ t('common.filters') }}
                        <span v-if="activeFilterCount" class="flex min-w-5 items-center justify-center rounded-full bg-ink px-1.5 text-[0.6875rem] leading-5 text-canvas">
                            {{ activeFilterCount }}
                        </span>
                    </button>
                    <p class="text-sm text-ink-muted" aria-live="polite">{{ t('companies.companyCount', { count: filteredCompanies.length }) }}</p>
                </div>
            </div>

            <div v-if="activeFilterCount" class="flex flex-wrap items-center gap-3 pt-5">
                <ActiveFilterChips v-model:educations="selectedEducations" v-model:sectors="selectedSectors" />
                <button type="button" class="text-sm font-medium text-ink underline decoration-hairline underline-offset-4 hover:decoration-ink" @click="clearAll">
                    {{ t('common.clearAll') }}
                </button>
            </div>

            <ul v-if="filteredCompanies.length" class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <li
                    v-for="(company, index) in filteredCompanies"
                    :key="company.id"
                    v-reveal="(index % 3) * 60"
                    v-spotlight
                    class="spot card-lift group relative flex flex-col p-2.5"
                >
                    <div class="relative flex aspect-[16/10] items-center justify-center overflow-hidden rounded-[var(--radius-input)] bg-canvas p-10 ring-1 ring-hairline">
                        <img
                            v-if="company.logo_url"
                            :src="company.logo_url"
                            :alt="company.name"
                            class="max-h-20 w-auto max-w-[70%] object-contain transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover:scale-[1.08]"
                            loading="lazy"
                            decoding="async"
                        />
                        <span v-else class="text-5xl font-semibold tracking-[-0.05em] text-ink-subtle" aria-hidden="true">{{ company.name.charAt(0) }}</span>
                        <span v-if="company.booth" class="t-mono absolute top-3 left-3 rounded-[var(--radius-chip)] bg-ink px-2.5 py-1 text-xs font-medium text-canvas">
                            {{ t('common.stand') }} {{ company.booth }}
                        </span>
                        <span
                            class="absolute top-3 right-3 flex size-9 items-center justify-center rounded-full bg-brand text-[#0b0f19] opacity-0 transition-opacity duration-300 group-focus-within:opacity-100 group-hover:opacity-100 max-md:opacity-100"
                            aria-hidden="true"
                        >
                            <PhPlus :size="16" weight="bold" />
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col px-3 pt-5 pb-3">
                        <p v-if="(company.sectors ?? []).length" class="truncate text-sm text-ink-muted">{{ (company.sectors ?? []).join(', ') }}</p>

                        <h2 class="t-h3 mt-1 text-ink">
                            <button
                                type="button"
                                class="text-left after:absolute after:inset-0 after:rounded-[var(--radius-card)] focus-visible:outline-none focus-visible:after:outline-2 focus-visible:after:outline-offset-2 focus-visible:after:outline-brand"
                                @click="openCompany(company)"
                            >
                                {{ company.name }}
                            </button>
                        </h2>

                        <p class="t-small mt-2 line-clamp-2">{{ htmlToText(company.description) || t('common.noDescription') }}</p>

                        <ul v-if="educationsPreview(company).length" class="mt-4 flex flex-wrap gap-1.5">
                            <li v-for="n in educationsPreview(company)" :key="company.id + '-e-' + n" class="chip chip-brand">{{ n }}</li>
                            <li v-if="(company.educations ?? []).length > 2" class="chip">+{{ (company.educations ?? []).length - 2 }}</li>
                        </ul>

                        <div class="mt-auto pt-5">
                            <a
                                v-if="company.website_url"
                                :href="company.website_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="relative z-10 inline-flex items-center gap-1.5 text-sm font-medium text-ink hover:text-brand-ink"
                            >
                                {{ t('common.website') }}
                                <PhArrowUpRight :size="14" weight="bold" aria-hidden="true" />
                            </a>
                            <span v-else class="text-sm text-ink-subtle">{{ t('common.noWebsite') }}</span>
                        </div>
                    </div>
                </li>
            </ul>

            <div v-else class="mt-10">
                <EmptyState v-if="!(props.companies ?? []).length" :icon="PhBuildings" :title="t('companies.finishing')" :text="t('companies.finishingDescription')">
                    <button type="button" class="btn btn-ink" @click="scrollToNewsletter">{{ t('companies.subscribe') }}</button>
                </EmptyState>
                <EmptyState v-else :icon="PhMagnifyingGlass" :title="t('companies.noneFound')" :text="t('companies.adjustFilters')">
                    <button type="button" class="btn btn-secondary" @click="(clearAll(), (q = ''))">{{ t('common.clearAll') }}</button>
                </EmptyState>
            </div>
        </section>
    </SiteLayout>

    <FilterSheet
        v-model:open="isFilterOpen"
        v-model:educations="selectedEducations"
        v-model:sectors="selectedSectors"
        :education-options="educationOptions"
        :sector-options="sectorOptions"
        :description="t('companies.filterDescription')"
        :education-placeholder="t('companies.searchEducation')"
        :sector-placeholder="t('companies.searchSector')"
    />

    <CompanyDialog v-model:open="isCompanyOpen" :company="companyDialogData" />
</template>
