<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import AppHeader from '@/components/AppHeader.vue';
import AppFooter from '@/components/AppFooter.vue';

type EventDto = {
    id: number
    title: string
    date: string // YYYY-MM-DD or ISO
}

type FilterOption = {
    id: number | string
    name: string
}

type CompanyDto = {
    id: number
    name: string
    logo_url?: string | null
    website_url?: string | null
    booth?: string | null
    description?: string | null

    // For filtering (names are easiest; controller can send these)
    educations?: string[] | null
    sectors?: string[] | null
}

const props = defineProps<{
    event: EventDto | null
    companies: CompanyDto[]
    eventKind?: 'upcoming' | 'most-recent'

    // Optional: if you want to show filter chips from DB
    educations?: FilterOption[]
    sectors?: FilterOption[]
}>();

const q = ref('');
const selectedEducations = ref<string[]>([]);
const selectedSectors = ref<string[]>([]);

const isFilterOpen = ref(false);
const educationFilterQuery = ref('');
const sectorFilterQuery = ref('');

const selectedCompany = ref<CompanyDto | null>(null);

const openCompany = (company: CompanyDto) => {
    selectedCompany.value = company;
    document.body.style.overflow = 'hidden';
};

const closeCompany = () => {
    selectedCompany.value = null;
    // Only unlock scroll if filters are not open
    if (!isFilterOpen.value) document.body.style.overflow = '';
};

const onKeydown = (e: KeyboardEvent) => {
    if (e.key !== 'Escape') return;
    if (selectedCompany.value) return closeCompany();
    if (isFilterOpen.value) return closeFilters();
};

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
});

const eventDateLabel = computed(() => {
    const v = props.event?.date;
    if (!v) return '';

    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return v;

    return d.toLocaleDateString('nl-NL', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

const headerSubtitle = computed(() => {
    if (!props.event) return 'Er is nog geen editie aangemaakt.';

    const kind = props.eventKind === 'upcoming' ? 'de komende editie' : 'de meest recente editie';
    const date = eventDateLabel.value;

    return date
        ? `Bekijk alle bedrijven die aanwezig zijn bij ${kind} op ${date}.`
        : `Bekijk alle bedrijven die aanwezig zijn bij ${kind}.`;
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

const filteredEducationOptions = computed(() => {
    const query = educationFilterQuery.value.trim().toLowerCase();
    if (!query) return educationOptions.value;
    return educationOptions.value.filter((n) => n.toLowerCase().includes(query));
});

const filteredSectorOptions = computed(() => {
    const query = sectorFilterQuery.value.trim().toLowerCase();
    if (!query) return sectorOptions.value;
    return sectorOptions.value.filter((n) => n.toLowerCase().includes(query));
});

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
    // Only unlock scroll if company modal is not open
    if (!selectedCompany.value) document.body.style.overflow = '';
};

const clearAll = () => {
    clearFilters();
    educationFilterQuery.value = '';
    sectorFilterQuery.value = '';
};

const filteredCompanies = computed(() => {
    const query = q.value.trim().toLowerCase();
    const edu = selectedEducations.value;
    const sec = selectedSectors.value;

    return (props.companies ?? [])
        .filter((c) => {
            if (!query) return true;

            const haystack = [
                c.name,
                c.booth ?? '',
                c.website_url ?? '',
                c.description ?? '',
                ...(c.educations ?? []),
                ...(c.sectors ?? [])
            ]
                .join(' ')
                .toLowerCase();

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
</script>

<template>

    <AppHeader />

    <main class="bg-background px-6 py-16 lg:px-16">
        <div class="mx-auto max-w-7xl">
            <header class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold text-primary">Bedrijven</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">Deelnemende bedrijven</h1>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">
                    {{ headerSubtitle }}
                </p>

                <div class="mt-10">
                    <div class="mx-auto max-w-2xl">
                        <input
                            v-model="q"
                            type="search"
                            placeholder="Zoek op bedrijfsnaam, stand, sector…"
                            class="h-12 w-full rounded-xl bg-background px-4 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                        />
                    </div>

                    <div class="mx-auto mt-5 flex max-w-2xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center justify-center gap-3 sm:justify-start">
                            <div class="inline-flex items-center gap-2 rounded-xl bg-accent px-3 py-2 text-xs font-semibold text-accent-foreground ring-1 ring-border">
                                <span class="h-2 w-2 rounded-full bg-primary"></span>
                                <span>{{ filteredCompanies.length }} bedrijven</span>
                            </div>

                            <div v-if="activeFilterCount" class="text-xs font-semibold text-muted-foreground">
                                {{ activeFilterCount }} filters actief
                            </div>
                        </div>

                        <div class="flex items-center justify-center gap-2 sm:justify-end">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl bg-background px-4 py-2 text-sm font-semibold text-foreground ring-1 ring-border transition hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                @click="openFilters"
                            >
                                Filters
                                <span v-if="activeFilterCount" class="ml-2 inline-flex min-w-6 items-center justify-center rounded-full bg-primary px-2 py-0.5 text-[11px] font-semibold text-primary-foreground">
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

                    <div v-if="activeFilterCount" class="mx-auto mt-4 flex max-w-4xl flex-wrap justify-center gap-2">
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
                </div>
            </header>

            <section v-if="filteredCompanies.length" class="mt-14 grid grid-cols-1 gap-8 md:grid-cols-2">
                <article
                    v-for="company in filteredCompanies"
                    :key="company.id"
                    role="button"
                    tabindex="0"
                    class="group cursor-pointer overflow-hidden rounded-2xl bg-background shadow-sm ring-1 ring-border transition hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-ring/40"
                    @click="openCompany(company)"
                    @keydown.enter.prevent="openCompany(company)"
                    @keydown.space.prevent="openCompany(company)"
                >
                    <div class="relative aspect-[16/7] w-full bg-accent/20">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <img
                                v-if="company.logo_url"
                                :src="company.logo_url"
                                :alt="company.name"
                                class="max-h-20 w-full max-w-[320px] object-contain px-8 opacity-90"
                                loading="lazy"
                                decoding="async"
                            />
                            <div v-else class="text-sm text-muted-foreground">Geen logo</div>
                        </div>

                        <div
                            v-if="company.booth"
                            class="absolute top-4 left-4 inline-flex items-center rounded-full bg-background/90 px-3 py-1 text-xs font-semibold text-foreground ring-1 ring-border"
                        >
                            Stand {{ company.booth }}
                        </div>
                    </div>

                    <div class="p-6">
                        <h2 class="text-xl font-semibold tracking-tight text-foreground">
                            {{ company.name }}
                        </h2>

                        <p v-if="company.description" class="mt-3 line-clamp-2 text-sm leading-relaxed text-muted-foreground">
                            {{ company.description }}
                        </p>
                        <p v-else class="mt-3 text-sm leading-relaxed text-muted-foreground">Geen beschrijving.</p>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <div class="text-xs font-semibold text-muted-foreground">Opleidingen</div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span
                                        v-for="n in (company.educations ?? []).slice(0, 4)"
                                        :key="company.id + '-e-' + n"
                                        class="inline-flex items-center rounded-full bg-orange-500/15 px-3 py-1 text-xs font-semibold text-orange-700 ring-1 ring-orange-500/30 dark:text-orange-300"
                                    >
                                        {{ n }}
                                    </span>

                                    <span
                                        v-if="!(company.educations ?? []).length"
                                        class="inline-flex items-center rounded-full bg-background px-3 py-1 text-xs font-semibold text-muted-foreground ring-1 ring-border"
                                    >
                                        Geen
                                    </span>

                                    <span
                                        v-else-if="(company.educations ?? []).length > 4"
                                        class="inline-flex items-center rounded-full bg-background px-3 py-1 text-xs font-semibold text-muted-foreground ring-1 ring-border"
                                    >
                                        +{{ (company.educations ?? []).length - 4 }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold text-muted-foreground">Sectoren</div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span
                                        v-for="n in (company.sectors ?? []).slice(0, 4)"
                                        :key="company.id + '-s-' + n"
                                        class="inline-flex items-center rounded-full bg-blue-500/15 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-500/30 dark:text-blue-300"
                                    >
                                        {{ n }}
                                    </span>

                                    <span
                                        v-if="!(company.sectors ?? []).length"
                                        class="inline-flex items-center rounded-full bg-background px-3 py-1 text-xs font-semibold text-muted-foreground ring-1 ring-border"
                                    >
                                        Geen
                                    </span>

                                    <span
                                        v-else-if="(company.sectors ?? []).length > 4"
                                        class="inline-flex items-center rounded-full bg-background px-3 py-1 text-xs font-semibold text-muted-foreground ring-1 ring-border"
                                    >
                                        +{{ (company.sectors ?? []).length - 4 }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <a
                                v-if="company.website_url"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                :href="company.website_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Website
                            </a>

                            <span
                                v-else
                                class="inline-flex items-center justify-center rounded-xl bg-background px-5 py-2.5 text-sm font-semibold text-foreground ring-1 ring-border"
                            >
                Geen website
              </span>
                        </div>
                    </div>
                </article>
            </section>

            <section v-else class="mx-auto mt-14 max-w-3xl rounded-2xl bg-background p-10 text-center shadow-sm ring-1 ring-border">
                <h2 class="text-base font-semibold text-foreground">Geen bedrijven gevonden</h2>
                <p class="mt-2 text-sm text-muted-foreground">Pas je zoekopdracht of filters aan.</p>
            </section>
        </div>
        <Teleport to="body">
            <div v-if="isFilterOpen" class="fixed inset-0 z-[100]" aria-modal="true" role="dialog">
                <button class="absolute inset-0 bg-black/40" type="button" @click="closeFilters" aria-label="Sluiten"></button>

                <div class="absolute right-0 top-0 h-full w-full max-w-md overflow-hidden bg-background shadow-xl ring-1 ring-border">
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
                            <button
                                v-if="activeFilterCount"
                                type="button"
                                class="text-sm font-semibold text-primary hover:underline"
                                @click="clearAll"
                            >
                                Alles wissen
                            </button>
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
                                <label v-for="name in filteredEducationOptions" :key="'f-edu-' + name" class="flex items-center gap-3 rounded-xl bg-accent/40 px-3 py-2 ring-1 ring-border/70">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4"
                                        :checked="selectedEducations.includes(name)"
                                        @change="toggle(selectedEducations, name)"
                                    />
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
                                <label v-for="name in filteredSectorOptions" :key="'f-sec-' + name" class="flex items-center gap-3 rounded-xl bg-accent/40 px-3 py-2 ring-1 ring-border/70">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4"
                                        :checked="selectedSectors.includes(name)"
                                        @change="toggle(selectedSectors, name)"
                                    />
                                    <span class="text-sm font-medium text-foreground">{{ name }}</span>
                                </label>

                                <div v-if="!filteredSectorOptions.length" class="text-sm text-muted-foreground">Geen resultaten.</div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute bottom-0 left-0 right-0 border-t border-border bg-background px-5 py-4">
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

        <Teleport to="body">
            <div v-if="selectedCompany" class="fixed inset-0 z-[110]" aria-modal="true" role="dialog">
                <button class="absolute inset-0 bg-black/50" type="button" @click="closeCompany" aria-label="Sluiten"></button>

                <div class="absolute left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-3xl -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-2xl bg-background shadow-2xl ring-1 ring-border">
                    <div class="flex items-center justify-between gap-4 border-b border-border px-6 py-5">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-muted-foreground">Bedrijf</div>
                            <h2 class="mt-1 truncate text-2xl font-semibold tracking-tight text-foreground">
                                {{ selectedCompany.name }}
                            </h2>
                            <div v-if="selectedCompany.booth" class="mt-2 inline-flex items-center rounded-full bg-accent px-3 py-1 text-xs font-semibold text-accent-foreground ring-1 ring-border">
                                Stand {{ selectedCompany.booth }}
                            </div>
                        </div>

                        <div class="flex flex-1 items-center justify-center">
                            <div class="flex h-14 w-full max-w-[320px] items-center justify-center rounded-lg bg-accent/20 p-[5px] ring-1 ring-border">
                                <img
                                    v-if="selectedCompany.logo_url"
                                    :src="selectedCompany.logo_url"
                                    :alt="selectedCompany.name"
                                    class="h-full w-full object-contain"
                                    loading="lazy"
                                    decoding="async"
                                    @error="(e) => (((e.target as HTMLImageElement).style.display = 'none'))"
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

                    <div class="max-h-[75vh] overflow-y-auto px-6 py-6">
                        <div>
                            <div class="text-sm font-semibold text-foreground">Beschrijving</div>
                            <p v-if="selectedCompany.description" class="mt-2 whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                {{ selectedCompany.description }}
                            </p>
                            <p v-else class="mt-2 text-sm text-muted-foreground">Geen beschrijving.</p>

                            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                                <div>
                                    <div class="text-sm font-semibold text-foreground">Opleidingen</div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span
                                            v-for="n in (selectedCompany.educations ?? [])"
                                            :key="'m-edu-' + n"
                                            class="inline-flex items-center rounded-full bg-orange-500/15 px-3 py-1 text-xs font-semibold text-orange-700 ring-1 ring-orange-500/30 dark:text-orange-300"
                                        >
                                            {{ n }}
                                        </span>
                                        <span
                                            v-if="!(selectedCompany.educations ?? []).length"
                                            class="inline-flex items-center rounded-full bg-background px-3 py-1 text-xs font-semibold text-muted-foreground ring-1 ring-border"
                                        >
                                            Geen
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <div class="text-sm font-semibold text-foreground">Sectoren</div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span
                                            v-for="n in (selectedCompany.sectors ?? [])"
                                            :key="'m-sec-' + n"
                                            class="inline-flex items-center rounded-full bg-blue-500/15 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-500/30 dark:text-blue-300"
                                        >
                                            {{ n }}
                                        </span>
                                        <span
                                            v-if="!(selectedCompany.sectors ?? []).length"
                                            class="inline-flex items-center rounded-full bg-background px-3 py-1 text-xs font-semibold text-muted-foreground ring-1 ring-border"
                                        >
                                            Geen
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-border px-6 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <a
                                v-if="selectedCompany.website_url"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                :href="selectedCompany.website_url"
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

    </main>

    <AppFooter />

</template>

<style scoped>

</style>

