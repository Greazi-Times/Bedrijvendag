<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PhArrowLeft, PhFunnelSimple, PhMapPin, PhMapTrifold } from '@phosphor-icons/vue';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import ActiveFilterChips from '@/components/site/ActiveFilterChips.vue';
import CompanyDialog from '@/components/site/CompanyDialog.vue';
import FilterSheet from '@/components/site/FilterSheet.vue';
import PageIntro from '@/components/site/PageIntro.vue';
import SearchField from '@/components/site/SearchField.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import { useTranslations } from '@/i18n';
import { formatDate } from '@/lib/date';
import { mapPointIcon, mapPointMarkerClass, standBadgeClass, standDisplayCode } from '@/lib/floorplan';
import type { CompanyDialogData } from '@/types/site';

type Stand = {
    id: number | string;
    code: string; // stand number
    stand_type?: 'company' | 'partner' | null;
    company_name?: string | null;
    company_logo?: string | null;
    company_description?: string | null;
    company_website_url?: string | null;
    company_educations?: string[] | null;
    company_sectors?: string[] | null;
    x_percent?: number | null;
    y_percent?: number | null;
};

type MapPoint = {
    id: number | string;
    label: string;
    type: string;
    x_percent?: number | null;
    y_percent?: number | null;
};

type EventMap = {
    title: string;
    date?: string | null;
    // absolute or relative URL to the map image (e.g. from Storage::url())
    image_url: string;
    // optional natural size if you want nicer zoom clamping
    width?: number | null;
    height?: number | null;
};

type FilterOption = {
    id: number | string;
    name: string;
};

const props = defineProps<{
    event: {
        id: number | string;
        title: string;
        date?: string | null;
    };
    map: EventMap;
    stands: Stand[];
    mapPoints?: MapPoint[];
    backHref?: string;
    educations?: FilterOption[];
    sectors?: FilterOption[];
}>();
const { t, dateLocale } = useTranslations();

const eventDate = computed(() => formatDate(props.event.date, dateLocale.value, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));

const query = ref('');
const selectedStandId = ref<Stand['id'] | null>(null);
const selectedCompany = ref<Stand | null>(null);
const mapImageRef = ref<HTMLImageElement | null>(null);
const mapPanelRef = ref<HTMLElement | null>(null);
const isCompanyOpen = ref(false);
const mapImageHeight = ref(0);

const selectedEducations = ref<string[]>([]);
const selectedSectors = ref<string[]>([]);

const isFilterOpen = ref(false);

const normalizedQuery = computed(() => query.value.trim().toLowerCase());

const educationOptions = computed<string[]>(() => {
    if (props.educations?.length) return props.educations.map((e) => e.name);

    const set = new Set<string>();
    for (const stand of props.stands) for (const n of stand.company_educations ?? []) set.add(n);
    return Array.from(set).sort((a, b) => a.localeCompare(b, 'nl'));
});

const sectorOptions = computed<string[]>(() => {
    if (props.sectors?.length) return props.sectors.map((s) => s.name);

    const set = new Set<string>();
    for (const stand of props.stands) for (const n of stand.company_sectors ?? []) set.add(n);
    return Array.from(set).sort((a, b) => a.localeCompare(b, 'nl'));
});

const filteredStands = computed(() => {
    const q = normalizedQuery.value;
    const edu = selectedEducations.value;
    const sec = selectedSectors.value;

    return [...props.stands]
        .filter((s) => {
            if (!q) return true;

            const haystack = [
                s.code ?? '',
                s.company_name ?? '',
                s.company_website_url ?? '',
                s.company_description ?? '',
                ...(s.company_educations ?? []),
                ...(s.company_sectors ?? []),
            ]
                .join(' ')
                .toLowerCase();

            return haystack.includes(q);
        })
        .filter((s) => {
            if (!edu.length) return true;
            const names = s.company_educations ?? [];
            return edu.some((x) => names.includes(x));
        })
        .filter((s) => {
            if (!sec.length) return true;
            const names = s.company_sectors ?? [];
            return sec.some((x) => names.includes(x));
        })
        .sort((a, b) => {
            const aTypeOrder = a.stand_type === 'partner' ? 0 : 1;
            const bTypeOrder = b.stand_type === 'partner' ? 0 : 1;

            if (aTypeOrder !== bTypeOrder) return aTypeOrder - bTypeOrder;

            const aNum = parseInt(String(a.code).replace(/[^0-9]/g, '')) || 0;
            const bNum = parseInt(String(b.code).replace(/[^0-9]/g, '')) || 0;

            if (aNum !== bNum) return aNum - bNum;

            return String(a.code).localeCompare(String(b.code));
        });
});

const standsWithCoords = computed(() => {
    return filteredStands.value.filter((s) => typeof s.x_percent === 'number' && typeof s.y_percent === 'number');
});

const mapPointsWithCoords = computed(() => {
    return (props.mapPoints ?? []).filter((point) => typeof point.x_percent === 'number' && typeof point.y_percent === 'number');
});

const selectedStand = computed(() => {
    if (selectedStandId.value == null) return null;
    return props.stands.find((s) => s.id === selectedStandId.value) ?? null;
});

// The stand list matches the map height only when they sit side by side.
const isDesktop = ref(false);

const updateMapImageHeight = () => {
    mapImageHeight.value = mapImageRef.value?.clientHeight ?? 0;
    isDesktop.value = window.matchMedia('(min-width: 1024px)').matches;
};

const clearAll = () => {
    selectedEducations.value = [];
    selectedSectors.value = [];
};

const activeFilterCount = computed(() => selectedEducations.value.length + selectedSectors.value.length);

function standDisplayName(stand: Stand) {
    return stand.company_name ?? t('map.noOrganisation');
}

function selectStand(id: Stand['id'], reveal = false) {
    selectedStandId.value = id;

    // On stacked (mobile/tablet) layouts the list sits below the map; bring the marker into view.
    if (reveal && window.matchMedia('(max-width: 1023px)').matches) {
        mapPanelRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function clearSelection() {
    selectedStandId.value = null;
}

const openCompany = (stand: Stand) => {
    selectedCompany.value = stand;
    isCompanyOpen.value = true;
};

const companyDialogData = computed<CompanyDialogData | null>(() => {
    const s = selectedCompany.value;
    if (!s) return null;

    return {
        name: standDisplayName(s),
        kind: s.stand_type === 'partner' ? t('map.partner') : t('common.company'),
        stand: standDisplayCode(s),
        logoUrl: s.company_logo,
        description: s.company_description,
        educations: s.company_educations,
        sectors: s.company_sectors,
        showSectors: s.stand_type !== 'partner',
        websiteUrl: s.company_website_url,
    };
});

onMounted(() => {
    window.addEventListener('resize', updateMapImageHeight);
    nextTick(updateMapImageHeight);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateMapImageHeight);
});
</script>

<template>
    <Head :title="`${event.title} - ${t('map.title')}`" />

    <SiteLayout>
        <PageIntro :eyebrow="eventDate ? `${t('map.title')} · ${eventDate}` : t('map.title')" :title="event.title" :lead="t('map.intro')">
            <Link v-if="backHref" :href="backHref" class="btn btn-secondary btn-sm">
                <PhArrowLeft :size="16" aria-hidden="true" />
                {{ t('map.back') }}
            </Link>
        </PageIntro>

        <section class="site-container pt-10 pb-24 md:pt-14 md:pb-32">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 lg:items-start">
                <!-- Map -->
                <div ref="mapPanelRef" class="min-w-0 scroll-mt-20 lg:col-span-8">
                    <div class="overflow-hidden rounded-[var(--radius-card)] border border-hairline bg-white">
                        <div class="relative">
                            <template v-if="map.image_url">
                                <img
                                    ref="mapImageRef"
                                    :src="map.image_url"
                                    :alt="t('map.imageAlt', { event: event.title })"
                                    class="block h-auto w-full select-none"
                                    draggable="false"
                                    @load="updateMapImageHeight"
                                />

                                <button
                                    v-for="stand in standsWithCoords"
                                    :key="`marker-${stand.id}`"
                                    type="button"
                                    class="group absolute z-10 flex h-6 min-w-6 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full px-1.5 text-[11px] font-semibold shadow-[0_2px_6px_rgb(11_15_25/0.3)] ring-2 ring-white transition-transform duration-200 hover:z-20 hover:scale-115 focus-visible:z-20 focus-visible:scale-115 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0b0f19] sm:h-7 sm:min-w-7 sm:text-xs"
                                    :class="selectedStandId === stand.id ? 'z-20 scale-125 bg-[#0b0f19] text-white' : standBadgeClass(stand)"
                                    :style="{ left: `${stand.x_percent}%`, top: `${stand.y_percent}%` }"
                                    :aria-label="`${t('common.stand')} ${standDisplayCode(stand)} ${standDisplayName(stand)}`"
                                    :aria-pressed="selectedStandId === stand.id"
                                    @click="selectStand(stand.id)"
                                >
                                    <span
                                        v-if="selectedStandId === stand.id"
                                        class="pointer-events-none absolute inset-0 -z-10 rounded-full bg-[#0b0f19]/50 motion-safe:animate-ping"
                                        aria-hidden="true"
                                    ></span>
                                    {{ standDisplayCode(stand) }}
                                    <span
                                        class="pointer-events-none absolute bottom-full left-1/2 mb-2 hidden w-max max-w-56 -translate-x-1/2 rounded-lg bg-[#0b0f19] px-3 py-1.5 text-center text-xs leading-snug font-medium text-white shadow-xl group-hover:block group-focus-visible:block"
                                        aria-hidden="true"
                                    >
                                        {{ standDisplayName(stand) }}
                                    </span>
                                </button>

                                <span
                                    v-for="point in mapPointsWithCoords"
                                    :key="`map-point-${point.id}`"
                                    tabindex="0"
                                    role="img"
                                    class="group absolute z-10 flex size-8 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full shadow-[0_2px_8px_rgb(11_15_25/0.3)] ring-2 transition-transform hover:z-20 hover:scale-110 focus-visible:z-20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0b0f19] sm:size-9"
                                    :class="mapPointMarkerClass(point)"
                                    :style="{ left: `${point.x_percent}%`, top: `${point.y_percent}%` }"
                                    :aria-label="point.label"
                                >
                                    <component :is="mapPointIcon(point)" :size="18" weight="bold" aria-hidden="true" />
                                    <span
                                        class="pointer-events-none absolute bottom-full left-1/2 mb-2 hidden w-max max-w-56 -translate-x-1/2 rounded-lg bg-[#0b0f19] px-3 py-1.5 text-center text-xs leading-snug font-medium text-white shadow-xl group-hover:block group-focus-visible:block"
                                        aria-hidden="true"
                                    >
                                        {{ point.label }}
                                    </span>
                                </span>
                            </template>

                            <div v-else class="flex min-h-[360px] flex-col items-center justify-center gap-4 bg-surface px-6 text-center">
                                <PhMapTrifold :size="32" class="text-ink-muted" aria-hidden="true" />
                                <p class="t-small">{{ t('map.noMapConfigured') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stand list -->
                <aside class="min-w-0 lg:col-span-4" :aria-label="t('map.companies')">
                    <div class="card flex min-h-0 flex-col p-3 sm:p-4" :style="mapImageHeight > 0 && isDesktop ? { height: `${mapImageHeight + 2}px` } : undefined">
                        <SearchField id="stand-search" v-model="query" :label="t('common.search')" :placeholder="t('map.searchPlaceholder')" />

                        <div class="mt-3 flex items-center justify-between gap-3 px-1">
                            <p class="text-sm text-ink-muted" aria-live="polite">{{ t('map.standCount', { count: filteredStands.length }) }}</p>
                            <button type="button" class="btn btn-ghost btn-sm -mr-2" @click="isFilterOpen = true">
                                <PhFunnelSimple :size="16" aria-hidden="true" />
                                {{ t('common.filters') }}
                                <span v-if="activeFilterCount" class="flex min-w-5 items-center justify-center rounded-full bg-ink px-1.5 text-[0.6875rem] leading-5 text-canvas">
                                    {{ activeFilterCount }}
                                </span>
                            </button>
                        </div>

                        <div v-if="activeFilterCount" class="mt-2 flex flex-wrap items-center gap-2 px-1">
                            <ActiveFilterChips v-model:educations="selectedEducations" v-model:sectors="selectedSectors" />
                            <button type="button" class="text-sm font-medium text-ink underline decoration-hairline underline-offset-4 hover:decoration-ink" @click="clearAll">
                                {{ t('common.clear') }}
                            </button>
                        </div>

                        <ul class="-mx-1 mt-3 min-h-0 flex-1 space-y-0.5 overflow-y-auto overscroll-contain px-1 max-lg:max-h-[30rem]">
                            <li
                                v-for="stand in filteredStands"
                                :key="stand.id"
                                class="flex items-center gap-2 rounded-[var(--radius-input)] p-1.5 pr-1 transition-colors"
                                :class="selectedStandId === stand.id ? 'bg-surface-2' : 'hover:bg-surface'"
                            >
                                <button
                                    type="button"
                                    class="flex min-w-0 flex-1 items-center gap-3 rounded-lg py-0.5 text-left focus-visible:outline-offset-1"
                                    :aria-pressed="selectedStandId === stand.id"
                                    @click="selectStand(stand.id, true)"
                                >
                                    <span class="flex h-8 min-w-8 shrink-0 items-center justify-center rounded-full px-1.5 text-xs font-semibold" :class="standBadgeClass(stand)">
                                        {{ standDisplayCode(stand) }}
                                    </span>
                                    <span class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white ring-1 ring-hairline">
                                        <img v-if="stand.company_logo" :src="stand.company_logo" alt="" class="size-full object-contain p-1" loading="lazy" />
                                        <PhMapPin v-else :size="16" class="text-[#6b7180]" aria-hidden="true" />
                                    </span>
                                    <span class="min-w-0 flex-1 truncate text-sm font-medium" :class="stand.company_name ? 'text-ink' : 'text-ink-subtle'">
                                        {{ standDisplayName(stand) }}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    class="min-h-9 shrink-0 rounded-[var(--radius-input)] px-3 text-[0.8125rem] font-medium text-ink-muted transition-colors hover:bg-canvas hover:text-ink"
                                    :aria-label="`${t('map.readMore')}: ${standDisplayName(stand)}`"
                                    @click.stop="openCompany(stand)"
                                >
                                    {{ t('map.readMore') }}
                                </button>
                            </li>
                            <li v-if="!filteredStands.length" class="t-small px-2 py-10 text-center">{{ t('common.noResults') }}</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </section>
    </SiteLayout>

    <FilterSheet
        v-model:open="isFilterOpen"
        v-model:educations="selectedEducations"
        v-model:sectors="selectedSectors"
        :education-options="educationOptions"
        :sector-options="sectorOptions"
        :description="t('map.filterDescription')"
        :education-placeholder="t('map.searchEducation')"
        :sector-placeholder="t('map.searchSector')"
    />

    <CompanyDialog v-model:open="isCompanyOpen" :company="companyDialogData" />
</template>
