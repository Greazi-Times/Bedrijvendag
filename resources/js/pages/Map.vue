<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Beer, Building2, DoorOpen, Info, MapPin, Search, Utensils } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';

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

const query = ref('');
const selectedStandId = ref<Stand['id'] | null>(null);
const selectedCompany = ref<Stand | null>(null);
const mapImageRef = ref<HTMLImageElement | null>(null);
const mapImageHeight = ref(0);

const selectedEducations = ref<string[]>([]);
const selectedSectors = ref<string[]>([]);

const isFilterOpen = ref(false);
const educationFilterQuery = ref('');
const sectorFilterQuery = ref('');

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

const filteredEducationOptions = computed(() => {
    const q = educationFilterQuery.value.trim().toLowerCase();
    if (!q) return educationOptions.value;
    return educationOptions.value.filter((n) => n.toLowerCase().includes(q));
});

const filteredSectorOptions = computed(() => {
    const q = sectorFilterQuery.value.trim().toLowerCase();
    if (!q) return sectorOptions.value;
    return sectorOptions.value.filter((n) => n.toLowerCase().includes(q));
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

const updateMapImageHeight = () => {
    mapImageHeight.value = mapImageRef.value?.clientHeight ?? 0;
};

const toggle = (arr: string[], value: string) => {
    const i = arr.indexOf(value);
    if (i >= 0) arr.splice(i, 1);
    else arr.push(value);
};

const clearFilters = () => {
    selectedEducations.value = [];
    selectedSectors.value = [];
};

const openFilters = () => {
    isFilterOpen.value = true;
    document.body.style.overflow = 'hidden';
};

const closeFilters = () => {
    isFilterOpen.value = false;
    if (!selectedCompany.value) document.body.style.overflow = '';
};

const clearAll = () => {
    clearFilters();
    educationFilterQuery.value = '';
    sectorFilterQuery.value = '';
};

const activeFilterCount = computed(() => selectedEducations.value.length + selectedSectors.value.length);

function standDisplayCode(stand: Stand) {
    const code = String(stand.code).replace(/^P/i, '');

    return stand.stand_type === 'partner' ? `P${code}` : code;
}

function standDisplayName(stand: Stand) {
    return stand.company_name ?? 'Geen organisatie ingesteld';
}

function standBadgeClass(stand: Stand) {
    return stand.stand_type === 'partner'
        ? 'bg-sky-100 text-sky-950 ring-sky-200 dark:bg-sky-400/20 dark:text-sky-100 dark:ring-sky-300/30'
        : 'bg-orange-100 text-orange-950 ring-orange-200 dark:bg-orange-400/20 dark:text-orange-100 dark:ring-orange-300/30';
}

function standRowClass(stand: Stand) {
    return selectedStandId.value === stand.id
        ? 'border-primary/35 bg-primary/5 shadow-sm ring-1 ring-primary/20'
        : 'border-border/70 bg-white/55 hover:border-primary/25 hover:bg-white/90 dark:bg-white/5 dark:hover:bg-white/10';
}

function mapPointIcon(point: MapPoint) {
    return (
        {
            bar: Beer,
            info: Info,
            lunch: Utensils,
            entrance: DoorOpen,
        }[point.type] ?? MapPin
    );
}

function mapPointMarkerClass(point: MapPoint) {
    return (
        {
            bar: 'bg-amber-500 text-amber-950 ring-white/95',
            info: '!h-7 !w-7 !min-w-7 !px-0 bg-sky-500 text-white ring-white/95 [&_svg]:h-4 [&_svg]:w-4',
            lunch: 'bg-emerald-500 text-white ring-white/95',
            entrance: 'bg-violet-500 text-white ring-white/95',
        }[point.type] ?? 'bg-slate-600 text-white ring-white/95'
    );
}

function selectStand(id: Stand['id']) {
    selectedStandId.value = id;
}

function clearSelection() {
    selectedStandId.value = null;
}

const openCompany = (stand: Stand) => {
    selectedCompany.value = stand;
    document.body.style.overflow = 'hidden';
};

const closeCompany = () => {
    selectedCompany.value = null;
    if (!isFilterOpen.value) document.body.style.overflow = '';
};

const onKeydown = (e: KeyboardEvent) => {
    if (e.key !== 'Escape') return;
    if (selectedCompany.value) return closeCompany();
    if (isFilterOpen.value) return closeFilters();
};

const sanitizeHtml = (value: string) => {
    try {
        const doc = new DOMParser().parseFromString(String(value ?? ''), 'text/html');

        doc.querySelectorAll('script, style, iframe, object, embed, link, meta').forEach((el) => el.remove());

        const all = doc.body.querySelectorAll('*');
        for (const el of all) {
            for (const attr of Array.from(el.attributes)) {
                const name = attr.name.toLowerCase();
                const val = (attr.value ?? '').trim().toLowerCase();

                if (name.startsWith('on')) {
                    el.removeAttribute(attr.name);
                    continue;
                }

                if ((name === 'href' || name === 'src') && val.startsWith('javascript:')) {
                    el.removeAttribute(attr.name);
                    continue;
                }
            }
        }

        return doc.body.innerHTML;
    } catch {
        return String(value ?? '');
    }
};

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('resize', updateMapImageHeight);
    nextTick(updateMapImageHeight);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('resize', updateMapImageHeight);
    document.body.style.overflow = '';
});
</script>

<template>
    <Head :title="`${event.title} - Plattegrond`" />

    <AppHeader />

    <section class="brand-hero px-4 py-10 md:px-8">
        <div class="relative z-10 mx-auto flex max-w-10/12 flex-col gap-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Building2 class="h-4 w-4" />
                        <span v-if="event.date" class="hidden sm:inline">•</span>
                        <span v-if="event.date" class="hidden sm:inline">{{ event.date }}</span>
                    </div>

                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-black dark:text-white">
                        {{ event.title }}
                    </h1>

                    <p class="mt-1 text-sm text-muted-foreground">
                        Zoek je stand, bedrijf of partner op de plattegrond. Klik op een markering of selecteer een stand uit de lijst.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="backHref"
                        :href="backHref"
                        class="inline-flex items-center justify-center rounded-xl border border-border bg-white/80 px-4 py-2 text-sm font-medium text-foreground shadow-sm hover:bg-accent"
                    >
                        Terug
                    </Link>
                </div>
            </div>

            <div class="grid items-stretch gap-6 lg:grid-cols-12">
                <!-- Map -->
                <div class="lg:col-span-8">
                    <div class="brand-card relative overflow-hidden rounded-2xl">
                        <div class="relative">
                            <!-- zoom layer -->
                            <div class="relative">
                                <template v-if="map.image_url">
                                    <img
                                        ref="mapImageRef"
                                        :src="map.image_url"
                                        :alt="`${event.title} map`"
                                        class="block h-auto w-full select-none"
                                        draggable="false"
                                        @load="updateMapImageHeight"
                                    />

                                    <button
                                        v-for="stand in standsWithCoords"
                                        :key="`marker-${stand.id}`"
                                        type="button"
                                        class="group absolute z-10 flex h-6 min-w-6 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full px-1.5 text-[11px] font-bold text-white shadow-md ring-2 ring-white/90 transition hover:z-20 hover:scale-110 focus-visible:z-20 focus-visible:ring-4 focus-visible:ring-primary/40 focus-visible:outline-none"
                                        :class="
                                            selectedStandId === stand.id
                                                ? 'bg-blue-600 ring-blue-200'
                                                : stand.stand_type === 'partner'
                                                  ? 'bg-secondary text-secondary-foreground ring-white/90'
                                                  : 'bg-primary ring-white/90'
                                        "
                                        :style="{ left: `${stand.x_percent}%`, top: `${stand.y_percent}%` }"
                                        :aria-label="`Stand ${standDisplayCode(stand)} ${standDisplayName(stand)}`"
                                        :title="standDisplayName(stand)"
                                        @click="selectStand(stand.id)"
                                    >
                                        <span
                                            v-if="selectedStandId === stand.id"
                                            class="pointer-events-none absolute inset-0 -z-10 animate-ping rounded-full bg-blue-500/70"
                                        ></span>
                                        {{ standDisplayCode(stand) }}
                                        <span
                                            class="pointer-events-none absolute bottom-full left-1/2 mb-2 hidden max-w-56 -translate-x-1/2 rounded-lg bg-gray-950 px-3 py-1.5 text-center text-xs leading-snug font-semibold text-white shadow-xl ring-1 ring-white/10 group-hover:block group-focus-visible:block"
                                        >
                                            {{ standDisplayName(stand) }}
                                        </span>
                                    </button>

                                    <button
                                        v-for="point in mapPointsWithCoords"
                                        :key="`map-point-${point.id}`"
                                        type="button"
                                        class="group absolute z-10 flex h-9 min-w-9 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full px-2 text-xs font-bold shadow-lg ring-2 transition hover:z-20 hover:scale-110 focus-visible:z-20 focus-visible:ring-4 focus-visible:ring-primary/40 focus-visible:outline-none"
                                        :class="mapPointMarkerClass(point)"
                                        :style="{ left: `${point.x_percent}%`, top: `${point.y_percent}%` }"
                                        :aria-label="point.label"
                                        :title="point.label"
                                    >
                                        <component :is="mapPointIcon(point)" class="h-5 w-5" aria-hidden="true" />
                                        <span
                                            class="pointer-events-none absolute bottom-full left-1/2 mb-2 hidden max-w-56 -translate-x-1/2 rounded-lg bg-gray-950 px-3 py-1.5 text-center text-xs leading-snug font-semibold text-white shadow-xl ring-1 ring-white/10 group-hover:block group-focus-visible:block"
                                        >
                                            {{ point.label }}
                                        </span>
                                    </button>
                                </template>

                                <div v-else class="flex min-h-[360px] items-center justify-center bg-accent/40 px-6 text-center text-sm text-muted-foreground">
                                    Er is nog geen plattegrond ingesteld voor dit evenement.
                                </div>
                            </div>

                            <!-- empty state when filtering hides all -->
                            <div v-if="filteredStands.length === 0" class="absolute inset-0 flex items-center justify-center px-6 text-center">
                                <div class="max-w-md rounded-2xl border border-border bg-white/90 p-5 text-sm text-foreground shadow-sm backdrop-blur">
                                    Geen stands gevonden die overeenkomen met je zoekopdracht.
                                </div>
                            </div>
                        </div>

                        <!-- Selected stand card -->
                        <div v-if="selectedStand" class="border-stroke dark:border-strokedark border-t p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-primary/10 px-2 py-1 text-xs font-semibold text-primary">
                                            Stand
                                            {{ standDisplayCode(selectedStand) }}
                                        </span>
                                    </div>
                                    <div class="mt-1 truncate text-sm font-medium text-black dark:text-white">
                                        {{ selectedStand.company_name ?? 'Geen organisatie ingesteld' }}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl border border-border bg-white/80 px-4 py-2 text-sm font-medium text-foreground hover:bg-accent"
                                    @click="clearSelection"
                                >
                                    Sluiten
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <aside class="lg:col-span-4 lg:h-full">
                    <div class="brand-card flex min-h-0 flex-col rounded-2xl p-4" :style="mapImageHeight > 0 ? { height: `${mapImageHeight}px` } : undefined">
                        <div class="flex items-center gap-2">
                            <Search class="h-4 w-4 text-muted-foreground" />
                            <input
                                v-model="query"
                                type="text"
                                placeholder="Zoek op standnummer, bedrijfsnaam of partner…"
                                class="w-full bg-transparent text-sm text-foreground placeholder:text-muted-foreground focus:outline-none"
                            />
                        </div>

                        <div class="mt-4 flex items-center justify-between text-xs text-muted-foreground">
                            <span>{{ filteredStands.length }} stands</span>
                            <button v-if="query" type="button" class="hover:underline" @click="query = ''">Wissen</button>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-2">
                            <div v-if="activeFilterCount" class="text-xs font-semibold text-muted-foreground">{{ activeFilterCount }} filters actief</div>
                            <div v-else></div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl bg-white/80 px-4 py-2 text-sm font-semibold text-foreground ring-1 ring-border transition hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                    @click="openFilters"
                                >
                                    Filters
                                    <span
                                        v-if="activeFilterCount"
                                        class="ml-2 inline-flex min-w-6 items-center justify-center rounded-full bg-primary px-2 py-0.5 text-[11px] font-semibold text-primary-foreground"
                                    >
                                        {{ activeFilterCount }}
                                    </span>
                                </button>

                                <button
                                    v-if="activeFilterCount"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-accent-foreground ring-1 ring-border transition hover:bg-accent/70 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                    @click="clearAll"
                                >
                                    Wissen
                                </button>
                            </div>
                        </div>

                        <div v-if="activeFilterCount" class="mt-4 flex flex-wrap gap-2">
                            <span
                                v-for="n in selectedEducations"
                                :key="'sel-edu-' + n"
                                class="inline-flex items-center gap-2 rounded-full bg-orange-500/15 px-3 py-1 text-xs font-semibold text-orange-700 ring-1 ring-orange-500/30 dark:text-orange-300"
                            >
                                {{ n }}
                                <button type="button" class="text-muted-foreground hover:text-foreground" @click="toggle(selectedEducations, n)">×</button>
                            </span>

                            <span
                                v-for="n in selectedSectors"
                                :key="'sel-sec-' + n"
                                class="inline-flex items-center gap-2 rounded-full bg-blue-500/15 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-500/30 dark:text-blue-300"
                            >
                                {{ n }}
                                <button type="button" class="text-muted-foreground hover:text-foreground" @click="toggle(selectedSectors, n)">×</button>
                            </span>
                        </div>

                        <div class="mt-4 min-h-0 flex-1 overflow-auto pr-1">
                            <div
                                v-for="stand in filteredStands"
                                :key="stand.id"
                                class="mb-2 flex items-center gap-3 rounded-xl border px-3 py-2 transition last:mb-0"
                                :class="standRowClass(stand)"
                            >
                                <button type="button" class="flex min-w-0 flex-1 items-center gap-3 text-left" @click="selectStand(stand.id)">
                                    <div
                                        class="flex h-10 min-w-10 shrink-0 items-center justify-center rounded-full px-2 text-sm font-semibold ring-1"
                                        :class="standBadgeClass(stand)"
                                    >
                                        {{ standDisplayCode(stand) }}
                                    </div>

                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-white">
                                        <img v-if="stand.company_logo" :src="stand.company_logo" :alt="stand.company_name ?? stand.code" class="h-full w-full object-contain p-1" />
                                        <span v-else class="text-xs font-medium text-muted-foreground"> Logo </span>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-medium text-foreground">
                                            {{ stand.company_name ?? 'Geen organisatie ingesteld' }}
                                        </div>
                                    </div>
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl bg-white/85 px-4 py-1.5 text-sm font-semibold text-foreground shadow-sm ring-1 ring-border transition hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none dark:bg-white/10 dark:hover:bg-white/15"
                                    @click.stop="openCompany(stand)"
                                >
                                    Lees meer
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="selectedCompany" class="fixed inset-0 z-[110]" aria-modal="true" role="dialog">
                <button class="absolute inset-0 bg-black/50" type="button" @click="closeCompany" aria-label="Sluiten"></button>

                <div
                    class="absolute top-1/2 left-1/2 flex max-h-[80vh] w-[calc(100%-2rem)] max-w-3xl -translate-x-1/2 -translate-y-1/2 flex-col overflow-hidden rounded-2xl bg-background shadow-2xl ring-1 ring-border"
                >
                    <div class="flex shrink-0 items-center justify-between gap-4 border-b border-border px-6 py-5">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-muted-foreground">{{ selectedCompany.stand_type === 'partner' ? 'Partner' : 'Bedrijf' }}</div>
                            <h2 class="mt-1 truncate text-2xl font-semibold tracking-tight text-foreground">
                                {{ selectedCompany.company_name ?? 'Geen organisatie ingesteld' }}
                            </h2>
                            <div class="mt-2 inline-flex items-center rounded-full bg-accent px-3 py-1 text-xs font-semibold text-accent-foreground ring-1 ring-border">
                                Stand
                                {{ standDisplayCode(selectedCompany) }}
                            </div>
                        </div>

                        <div class="flex flex-1 items-center justify-center">
                            <div class="flex h-14 w-full max-w-[320px] items-center justify-center rounded-lg bg-accent/20 p-[5px] ring-1 ring-border">
                                <img
                                    v-if="selectedCompany.company_logo"
                                    :src="selectedCompany.company_logo"
                                    :alt="selectedCompany.company_name ?? selectedCompany.code"
                                    class="h-full w-full object-contain"
                                    loading="lazy"
                                    decoding="async"
                                    @error="(e) => ((e.target as HTMLImageElement).style.display = 'none')"
                                />
                                <span v-else class="text-xs font-semibold text-muted-foreground">Geen logo</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-muted-foreground ring-1 ring-border transition hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                            @click="closeCompany"
                            aria-label="Sluiten"
                        >
                            ×
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto overscroll-contain px-6 py-6">
                        <div>
                            <div class="text-sm font-semibold text-foreground">Beschrijving</div>
                            <div
                                v-if="selectedCompany.company_description"
                                class="prose prose-sm mt-2 max-w-none text-muted-foreground dark:prose-invert prose-p:my-0 prose-ol:my-0 prose-ul:my-0 prose-li:my-0"
                                v-html="sanitizeHtml(selectedCompany.company_description)"
                            ></div>
                            <p v-else class="mt-2 text-sm text-muted-foreground">Geen beschrijving.</p>

                            <div class="mt-6 grid gap-6" :class="selectedCompany.stand_type !== 'partner' ? 'sm:grid-cols-2' : 'sm:grid-cols-1'">
                                <div>
                                    <div class="text-sm font-semibold text-foreground">Opleidingen</div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span
                                            v-for="n in selectedCompany.company_educations ?? []"
                                            :key="'m-edu-' + n"
                                            class="inline-flex items-center rounded-full bg-orange-500/15 px-3 py-1 text-xs font-semibold text-orange-700 ring-1 ring-orange-500/30 dark:text-orange-300"
                                        >
                                            {{ n }}
                                        </span>
                                        <span
                                            v-if="!(selectedCompany.company_educations ?? []).length"
                                            class="inline-flex items-center rounded-full bg-background px-3 py-1 text-xs font-semibold text-muted-foreground ring-1 ring-border"
                                        >
                                            Geen
                                        </span>
                                    </div>
                                </div>

                                <div v-if="selectedCompany.stand_type !== 'partner'">
                                    <div class="text-sm font-semibold text-foreground">Sectoren</div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span
                                            v-for="n in selectedCompany.company_sectors ?? []"
                                            :key="'m-sec-' + n"
                                            class="inline-flex items-center rounded-full bg-blue-500/15 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-500/30 dark:text-blue-300"
                                        >
                                            {{ n }}
                                        </span>
                                        <span
                                            v-if="!(selectedCompany.company_sectors ?? []).length"
                                            class="inline-flex items-center rounded-full bg-background px-3 py-1 text-xs font-semibold text-muted-foreground ring-1 ring-border"
                                        >
                                            Geen
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 border-t border-border px-6 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <a
                                v-if="selectedCompany.company_website_url"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                :href="selectedCompany.company_website_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Website
                            </a>

                            <div v-else></div>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-background px-4 py-2.5 text-sm font-semibold text-foreground ring-1 ring-border transition hover:bg-accent"
                                @click="closeCompany"
                            >
                                Sluiten
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="isFilterOpen" class="fixed inset-0 z-[100]" aria-modal="true" role="dialog">
                <button class="absolute inset-0 bg-black/40" type="button" @click="closeFilters" aria-label="Sluiten"></button>

                <div class="absolute top-0 right-0 h-full w-full max-w-md overflow-hidden bg-background shadow-xl ring-1 ring-border">
                    <div class="flex items-center justify-between gap-4 border-b border-border px-5 py-4">
                        <div>
                            <div class="text-sm font-semibold text-foreground">Filters</div>
                            <div class="mt-1 text-xs text-muted-foreground">Filter op opleiding en sector.</div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground ring-1 ring-border transition hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                            @click="closeFilters"
                            aria-label="Sluiten"
                        >
                            ×
                        </button>
                    </div>

                    <div class="h-full overflow-y-auto px-5 py-5 pb-28">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-semibold text-foreground">Geselecteerd: {{ activeFilterCount }}</div>
                            <button v-if="activeFilterCount" type="button" class="text-sm font-semibold text-primary hover:underline" @click="clearAll">Alles wissen</button>
                        </div>

                        <div class="mt-6">
                            <div class="text-sm font-semibold text-foreground">Opleidingen</div>
                            <input
                                v-model="educationFilterQuery"
                                type="search"
                                placeholder="Zoek opleiding…"
                                class="mt-3 h-10 w-full rounded-xl bg-background px-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />

                            <div class="mt-4 space-y-2">
                                <label
                                    v-for="name in filteredEducationOptions"
                                    :key="'f-edu-' + name"
                                    class="flex items-center gap-3 rounded-xl bg-accent/40 px-3 py-2 ring-1 ring-border/70"
                                >
                                    <input type="checkbox" class="h-4 w-4" :checked="selectedEducations.includes(name)" @change="toggle(selectedEducations, name)" />
                                    <span class="text-sm font-medium text-foreground">{{ name }}</span>
                                </label>

                                <div v-if="!filteredEducationOptions.length" class="text-sm text-muted-foreground">Geen resultaten.</div>
                            </div>
                        </div>

                        <div class="mt-8">
                            <div class="text-sm font-semibold text-foreground">Sectoren</div>
                            <input
                                v-model="sectorFilterQuery"
                                type="search"
                                placeholder="Zoek sector…"
                                class="mt-3 h-10 w-full rounded-xl bg-background px-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />

                            <div class="mt-4 space-y-2">
                                <label
                                    v-for="name in filteredSectorOptions"
                                    :key="'f-sec-' + name"
                                    class="flex items-center gap-3 rounded-xl bg-accent/40 px-3 py-2 ring-1 ring-border/70"
                                >
                                    <input type="checkbox" class="h-4 w-4" :checked="selectedSectors.includes(name)" @change="toggle(selectedSectors, name)" />
                                    <span class="text-sm font-medium text-foreground">{{ name }}</span>
                                </label>

                                <div v-if="!filteredSectorOptions.length" class="text-sm text-muted-foreground">Geen resultaten.</div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute right-0 bottom-0 left-0 border-t border-border bg-background px-5 py-4">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="inline-flex flex-1 items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                @click="closeFilters"
                            >
                                Toon resultaten
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-background px-4 py-2.5 text-sm font-semibold text-foreground ring-1 ring-border transition hover:bg-accent"
                                @click="clearAll"
                            >
                                Wissen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </section>

    <AppFooter />
</template>

<style scoped>
/* Optional: make marker text a bit crisper on scaled images */
button {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
</style>
