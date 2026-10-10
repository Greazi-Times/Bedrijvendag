<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { PhArrowUpRight, PhBuildings, PhImages, PhMapTrifold } from '@phosphor-icons/vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import CompanyDialog from '@/components/site/CompanyDialog.vue';
import SiteImage from '@/components/site/SiteImage.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import { useTranslations } from '@/i18n';
import { mapPointIcon, mapPointMarkerClass, standBadgeClass, standDisplayCode } from '@/lib/floorplan';
import { sanitizeHtml } from '@/lib/sanitize';
import type { CompanyDialogData } from '@/types/site';

interface Company {
    id: number;
    name: string;
    website_url: string | null;
    logo_url: string | null;
    description_html: string | null;
    sectors: string[];
    educations: string[];
    stand_number: string | number | null;
}

interface Stand {
    id: number | string;
    code: string;
    stand_type?: 'company' | 'partner' | null;
    company_name?: string | null;
    company_logo?: string | null;
    x_percent?: number | null;
    y_percent?: number | null;
}

interface MapPoint {
    id: number | string;
    label: string;
    type: string;
    x_percent?: number | null;
    y_percent?: number | null;
}

interface EditionEvent {
    id: number;
    title: string;
    starts_at: string | null;
    ends_at: string | null;
    location: string | null;
    description_html: string | null;
    header_image_url: string | null;
    map_url: string | null;
    gallery_url: string | null;
}

const props = defineProps<{
    event: EditionEvent;
    companies: Company[];
    stands: Stand[];
    mapPoints?: MapPoint[];
}>();
const { dateLocale, t } = useTranslations();

const sortedCompanies = computed(() => {
    const toNum = (v: Company['stand_number']) => {
        if (v === null || v === undefined) return null;
        if (typeof v === 'number') return Number.isFinite(v) ? v : null;
        const s = String(v).trim();
        if (!s) return null;
        const n = Number(s.replace(',', '.'));
        return Number.isFinite(n) ? n : null;
    };

    return [...props.companies].sort((a, b) => {
        const an = toNum(a.stand_number);
        const bn = toNum(b.stand_number);

        if (an === null && bn === null) return a.name.localeCompare(b.name);
        if (an === null) return 1;
        if (bn === null) return -1;
        if (an !== bn) return an - bn;
        return a.name.localeCompare(b.name);
    });
});

const hasMap = computed(() => !!props.event.map_url);

const standsWithCoords = computed(() => {
    return props.stands.filter((stand) => typeof stand.x_percent === 'number' && typeof stand.y_percent === 'number');
});

const mapPointsWithCoords = computed(() => {
    return (props.mapPoints ?? []).filter((point) => typeof point.x_percent === 'number' && typeof point.y_percent === 'number');
});

const mapImgEl = ref<HTMLImageElement | null>(null);
const mapImageHeight = ref<number>(0);
let mapResizeObserver: ResizeObserver | null = null;

function updateMapImageHeight() {
    const h = mapImgEl.value?.clientHeight ?? 0;
    mapImageHeight.value = h;
}

const selectedCompany = ref<Company | null>(null);
const isCompanyModalOpen = ref(false);

function openCompany(company: Company) {
    selectedCompany.value = company;
    isCompanyModalOpen.value = true;
}

const companyDialogData = computed<CompanyDialogData | null>(() => {
    const c = selectedCompany.value;
    if (!c) return null;

    return {
        name: c.name,
        kind: t('common.company'),
        stand: c.stand_number !== null ? String(c.stand_number) : null,
        logoUrl: c.logo_url,
        description: c.description_html,
        educations: c.educations,
        sectors: c.sectors,
        websiteUrl: c.website_url,
    };
});

function standDisplayName(stand: Stand) {
    return stand.company_name ?? t('map.noOrganisation');
}

onMounted(() => {
    nextTick(() => {
        updateMapImageHeight();

        if (mapImgEl.value && 'ResizeObserver' in window) {
            mapResizeObserver = new ResizeObserver(() => updateMapImageHeight());
            mapResizeObserver.observe(mapImgEl.value);
        }

        window.addEventListener('resize', updateMapImageHeight);
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateMapImageHeight);
    mapResizeObserver?.disconnect();
    mapResizeObserver = null;
});

function formatDateRange(start: string | null, end: string | null) {
    if (!start && !end) return t('common.unknownDate');

    const fmt = new Intl.DateTimeFormat(dateLocale.value, { day: '2-digit', month: 'short', year: 'numeric' });
    const s = start ? fmt.format(new Date(start)) : null;
    const e = end ? fmt.format(new Date(end)) : null;

    if (s && e && s !== e) return `${s} - ${e}`;
    return s ?? e ?? t('common.unknownDate');
}
</script>

<template>
    <Head :title="event.title" />

    <SiteLayout>
        <section class="theme-dark relative isolate overflow-hidden rounded-b-[var(--radius-panel)]">
            <div v-if="event.header_image_url" class="absolute inset-0 -z-10 [&>*]:size-full [&>img]:object-cover">
                <SiteImage :src="event.header_image_url" :alt="event.title" loading="eager" />
            </div>
            <div
                class="absolute inset-0 -z-10"
                :class="event.header_image_url ? 'bg-[linear-gradient(to_top,rgb(5_6_9/0.95)_5%,rgb(5_6_9/0.55)_55%,rgb(5_6_9/0.35)_100%)]' : ''"
                aria-hidden="true"
            ></div>

            <div class="site-container flex min-h-[26rem] flex-col justify-end pt-24 pb-14 md:min-h-[34rem] md:pb-20">
                <p class="enter chip chip-glass self-start">
                    {{ formatDateRange(event.starts_at, event.ends_at) }}<template v-if="event.location"> · {{ event.location }}</template>
                </p>
                <div class="mt-6 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <h1 class="enter t-h1 max-w-4xl text-white" style="--enter-delay: 60ms">{{ event.title }}</h1>
                    <a
                        v-if="event.gallery_url"
                        :href="event.gallery_url"
                        target="_blank"
                        rel="noreferrer"
                        class="enter btn btn-light shrink-0 self-start md:self-auto"
                        style="--enter-delay: 120ms"
                    >
                        <PhImages :size="18" aria-hidden="true" />
                        {{ t('events.gallery') }}
                        <PhArrowUpRight :size="14" weight="bold" aria-hidden="true" />
                    </a>
                </div>
            </div>
        </section>

        <section class="site-container py-16 md:py-24">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <h2 class="t-h3 text-ink lg:col-span-4">{{ t('common.description') }}</h2>
                <div class="lg:col-span-8">
                    <div v-if="event.description_html" class="rich max-w-[68ch]" v-html="sanitizeHtml(event.description_html)" />
                    <p v-else class="t-body">{{ t('common.noDescriptionAvailable') }}</p>
                </div>
            </div>
        </section>

        <section class="border-t border-hairline bg-surface/60 py-16 md:py-24">
            <div class="site-container grid grid-cols-1 gap-5 lg:grid-cols-12 lg:items-start">
                <div class="lg:col-span-8">
                    <h2 class="t-h3 mb-6 text-ink">{{ t('map.floorPlan') }}</h2>
                    <div v-if="hasMap" class="relative overflow-hidden rounded-[var(--radius-card)] border border-hairline bg-white">
                        <img ref="mapImgEl" :src="event.map_url ?? ''" :alt="t('map.imageAlt', { event: event.title })" class="block h-auto w-full" @load="updateMapImageHeight" />

                        <span
                            v-for="stand in standsWithCoords"
                            :key="`edition-marker-${stand.id}`"
                            tabindex="0"
                            role="img"
                            class="group absolute z-10 flex h-6 min-w-6 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full px-1.5 text-[11px] font-semibold shadow-[0_2px_6px_rgb(11_15_25/0.3)] ring-2 ring-white transition-transform hover:z-20 hover:scale-115 focus-visible:z-20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0b0f19] sm:h-7 sm:min-w-7 sm:text-xs"
                            :class="standBadgeClass(stand)"
                            :style="{ left: `${stand.x_percent}%`, top: `${stand.y_percent}%` }"
                            :aria-label="`${t('common.stand')} ${standDisplayCode(stand)} ${standDisplayName(stand)}`"
                        >
                            {{ standDisplayCode(stand) }}
                            <span
                                class="pointer-events-none absolute bottom-full left-1/2 mb-2 hidden w-max max-w-56 -translate-x-1/2 rounded-lg bg-[#0b0f19] px-3 py-1.5 text-center text-xs font-medium text-white shadow-xl group-hover:block group-focus-visible:block"
                                aria-hidden="true"
                            >
                                {{ standDisplayName(stand) }}
                            </span>
                        </span>

                        <span
                            v-for="point in mapPointsWithCoords"
                            :key="`edition-map-point-${point.id}`"
                            tabindex="0"
                            role="img"
                            class="group absolute z-10 flex size-8 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full shadow-[0_2px_8px_rgb(11_15_25/0.3)] ring-2 transition-transform hover:z-20 hover:scale-110 focus-visible:z-20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0b0f19] sm:size-9"
                            :class="mapPointMarkerClass(point)"
                            :style="{ left: `${point.x_percent}%`, top: `${point.y_percent}%` }"
                            :aria-label="point.label"
                        >
                            <component :is="mapPointIcon(point)" :size="18" weight="bold" aria-hidden="true" />
                            <span
                                class="pointer-events-none absolute bottom-full left-1/2 mb-2 hidden w-max max-w-56 -translate-x-1/2 rounded-lg bg-[#0b0f19] px-3 py-1.5 text-center text-xs font-medium text-white shadow-xl group-hover:block group-focus-visible:block"
                                aria-hidden="true"
                            >
                                {{ point.label }}
                            </span>
                        </span>
                    </div>
                    <div v-else class="card-muted flex min-h-64 flex-col items-center justify-center gap-3 text-center">
                        <PhMapTrifold :size="28" class="text-ink-muted" aria-hidden="true" />
                        <p class="t-small">{{ t('map.noMap') }}</p>
                    </div>
                </div>

                <aside class="lg:col-span-4" :aria-label="t('map.companies')">
                    <div class="mb-6 flex items-baseline justify-between">
                        <h2 class="t-h3 text-ink">{{ t('map.companies') }}</h2>
                        <span class="text-sm text-ink-muted">{{ companies.length }}</span>
                    </div>
                    <ul v-if="companies.length" class="card space-y-0.5 overflow-y-auto p-2" :style="mapImageHeight ? { maxHeight: mapImageHeight + 2 + 'px' } : undefined">
                        <li v-for="c in sortedCompanies" :key="c.id">
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 rounded-[var(--radius-input)] p-2 text-left transition-colors hover:bg-surface"
                                @click="openCompany(c)"
                            >
                                <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white ring-1 ring-hairline">
                                    <img v-if="c.logo_url" :src="c.logo_url" alt="" class="size-full object-contain p-1" loading="lazy" />
                                    <PhBuildings v-else :size="18" class="text-[#6b7180]" aria-hidden="true" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-ink">{{ c.name }}</span>
                                    <span v-if="c.stand_number" class="t-mono block text-xs text-ink-muted">{{ t('common.stand') }} {{ c.stand_number }}</span>
                                </span>
                            </button>
                        </li>
                    </ul>
                    <p v-else class="card-muted t-small p-6">{{ t('map.noCompanies') }}</p>
                </aside>
            </div>
        </section>
    </SiteLayout>

    <CompanyDialog v-model:open="isCompanyModalOpen" :company="companyDialogData" />
</template>
