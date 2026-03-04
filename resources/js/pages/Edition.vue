<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';

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
}>();

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

const mapImgEl = ref<HTMLImageElement | null>(null);
const mapImageHeight = ref<number>(0);
let mapResizeObserver: ResizeObserver | null = null;

function updateMapImageHeight() {
    const h = mapImgEl.value?.clientHeight ?? 0;
    mapImageHeight.value = h;
}

const selectedCompany = ref<Company | null>(null);
const isCompanyModalOpen = computed(() => selectedCompany.value !== null);

function openCompany(company: Company) {
    selectedCompany.value = company;
}

function closeCompany() {
    selectedCompany.value = null;
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') closeCompany();
}

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
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
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('resize', updateMapImageHeight);
    mapResizeObserver?.disconnect();
    mapResizeObserver = null;
});

function formatDateRange(start: string | null, end: string | null) {
    if (!start && !end) return 'Onbekende datum';

    const fmt = new Intl.DateTimeFormat('nl-NL', { day: '2-digit', month: 'short', year: 'numeric' });
    const s = start ? fmt.format(new Date(start)) : null;
    const e = end ? fmt.format(new Date(end)) : null;

    if (s && e && s !== e) return `${s} t/m ${e}`;
    return s ?? e ?? 'Onbekende datum';
}
</script>

<template>
    <AppHeader class="sticky top-0 z-50" />

    <main class="relative overflow-hidden bg-background px-6 py-14 lg:px-16">
      <div class="pointer-events-none absolute -top-24 -right-24 h-80 w-80 rounded-full bg-secondary/20" />
      <div class="pointer-events-none absolute top-36 -left-20 h-40 w-40 rounded-[2.75rem] bg-primary/20" />
      <div class="pointer-events-none absolute right-24 bottom-10 h-16 w-16 rounded-full bg-accent" />
        <div class="relative mx-auto max-w-7xl">

            <div v-if="event.header_image_url" class="overflow-hidden rounded-3xl shadow-sm ring-1 ring-border">
              <img :src="event.header_image_url" :alt="event.title" class="h-64 w-full object-cover" />
            </div>

            <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-sm text-muted-foreground">
                        {{ formatDateRange(event.starts_at, event.ends_at) }}
                        <span v-if="event.location"> • {{ event.location }}</span>
                    </p>
                    <h1 class="mt-2 truncate text-3xl font-semibold">{{ event.title }}</h1>
                </div>

                <a
                    v-if="event.gallery_url"
                    :href="event.gallery_url"
                    target="_blank"
                    rel="noreferrer"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm ring-1 ring-border transition hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                >
                    Fotoalbum
                </a>
            </div>

            <div class="mt-8 space-y-6">
                <!-- Description / info first (full width) -->
                <div class="rounded-3xl bg-background p-6 shadow-sm ring-1 ring-border">
                    <h2 class="text-sm font-medium">Beschrijving</h2>
                    <div
                        v-if="event.description_html"
                        class="prose prose-sm mt-4 max-w-none dark:prose-invert"
                        v-html="event.description_html"
                    />
                    <p v-else class="mt-4 text-sm text-muted-foreground">Geen beschrijving beschikbaar.</p>
                </div>

                <!-- Map left, companies right -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-5 lg:items-stretch">
                    <div class="lg:col-span-3 lg:h-full">
                        <div class="flex h-full min-h-0 flex-col rounded-3xl bg-background p-6 shadow-sm ring-1 ring-border">
                            <div class="flex h-6 items-center justify-between">
                              <h2 class="text-sm font-medium">Plattegrond</h2>
                              <span class="text-xs text-muted-foreground">&nbsp;</span>
                            </div>

                            <div v-if="hasMap" class="mt-4 overflow-hidden rounded-2xl ring-1 ring-border">
                              <img
                                ref="mapImgEl"
                                :src="event.map_url ?? ''"
                                alt="Plattegrond"
                                class="h-full w-full object-contain"
                                @load="updateMapImageHeight"
                              />
                            </div>

                            <p v-else class="mt-4 text-sm text-muted-foreground">Geen plattegrond beschikbaar.</p>
                        </div>
                    </div>

                    <aside class="lg:col-span-2 lg:h-full">
                        <div class="flex h-full min-h-0 flex-col rounded-3xl bg-background p-6 shadow-sm ring-1 ring-border">
                            <div class="flex h-6 items-center justify-between">
                                <h2 class="text-sm font-medium">Bedrijven</h2>
                                <span class="text-xs text-muted-foreground">{{ companies.length }}</span>
                            </div>

                            <div
                              v-if="companies.length"
                              class="mt-4 space-y-3 overflow-y-auto pr-1"
                              :style="mapImageHeight ? { height: mapImageHeight + 'px' } : undefined"
                            >
                                <button
                                    v-for="c in sortedCompanies"
                                    :key="c.id"
                                    type="button"
                                    class="flex w-full items-center gap-3 rounded-2xl bg-background p-3 text-left shadow-sm ring-1 ring-border transition hover:bg-accent hover:text-accent-foreground"
                                    @click="openCompany(c)"
                                >
                                    <div class="h-10 w-10 overflow-hidden rounded-xl bg-accent/20">
                                        <img
                                            v-if="c.logo_url"
                                            :src="c.logo_url"
                                            :alt="c.name"
                                            class="h-full w-full object-contain p-1"
                                        />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <p class="min-w-0 flex-1 truncate text-sm font-medium">{{ c.name }}</p>
                                            <span
                                                v-if="c.stand_number"
                                                class="shrink-0 rounded-full bg-background px-2.5 py-1 text-[11px] font-semibold text-foreground ring-1 ring-border"
                                            >
                                                Stand {{ c.stand_number }}
                                            </span>
                                        </div>
                                        <p v-if="c.website_url" class="truncate text-xs text-muted-foreground">{{ c.website_url }}</p>
                                    </div>

                                    <a
                                        v-if="c.website_url"
                                        :href="c.website_url"
                                        @click.stop
                                        target="_blank"
                                        rel="noreferrer"
                                        class="shrink-0 rounded-xl bg-background px-3 py-2 text-xs font-semibold text-foreground shadow-sm ring-1 ring-border transition hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                    >
                                        Website
                                    </a>
                                </button>
                            </div>

                            <p v-else class="mt-4 text-sm text-muted-foreground">Nog geen bedrijven gekoppeld.</p>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </main>

    <Teleport to="body">
        <div
            v-if="isCompanyModalOpen"
            class="fixed inset-0 z-50 bg-black/60"
            aria-label="Sluiten"
            @click="closeCompany"
        >
            <div class="absolute inset-0 flex items-center justify-center px-4 py-8">
                <div
                    class="w-full max-w-3xl max-h-[80vh] overflow-hidden rounded-3xl bg-background shadow-xl ring-1 ring-border flex flex-col"
                    role="dialog"
                    aria-modal="true"
                    @click.stop
                >
                    <div class="shrink-0 flex items-start justify-between gap-4 border-b border-border px-6 py-5">
                        <div class="min-w-0 flex items-center gap-3">
                            <div class="h-12 w-12 overflow-hidden rounded-2xl bg-accent/20 ring-1 ring-border">
                                <img
                                    v-if="selectedCompany?.logo_url"
                                    :src="selectedCompany.logo_url"
                                    :alt="selectedCompany?.name"
                                    class="h-full w-full object-contain p-2"
                                />
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate text-xl font-semibold">{{ selectedCompany?.name }}</h3>
                                <p v-if="selectedCompany?.stand_number" class="mt-1 text-sm text-muted-foreground">
                                    Stand {{ selectedCompany.stand_number }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-xl bg-background px-4 py-2 text-sm font-semibold text-foreground shadow-sm ring-1 ring-border transition hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                            @click="closeCompany"
                        >
                            Sluiten
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-6 overscroll-contain">
                        <div class="flex flex-wrap gap-2">
                        <span
                            v-for="(s, i) in (selectedCompany?.sectors ?? [])"
                            :key="`sector-${i}`"
                            class="rounded-full bg-background px-3 py-1 text-xs font-semibold text-foreground ring-1 ring-border"
                        >
                            {{ s }}
                        </span>

                            <span
                                v-for="(e, i) in (selectedCompany?.educations ?? [])"
                                :key="`edu-${i}`"
                                class="rounded-full bg-background px-3 py-1 text-xs font-semibold text-foreground ring-1 ring-border"
                            >
                            {{ e }}
                        </span>
                        </div>

                        <div
                            v-if="selectedCompany?.description_html"
                            class="prose prose-sm mt-5 max-w-none dark:prose-invert"
                            v-html="selectedCompany.description_html"
                        />
                        <p v-else class="mt-5 text-sm text-muted-foreground">Geen extra informatie beschikbaar.</p>

                        <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <a
                                v-if="selectedCompany?.website_url"
                                :href="selectedCompany.website_url"
                                target="_blank"
                                rel="noreferrer"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                            >
                                Website
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <AppFooter />
</template>
