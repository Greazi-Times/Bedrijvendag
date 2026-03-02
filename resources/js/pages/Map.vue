<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, MapPin, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';

type Stand = {
    id: number | string
    code: string // stand number
    company_name?: string | null
    x_percent?: number | null
    y_percent?: number | null
}

type EventMap = {
    title: string
    date?: string | null
    // absolute or relative URL to the map image (e.g. from Storage::url())
    image_url: string
    // optional natural size if you want nicer zoom clamping
    width?: number | null
    height?: number | null
}

const props = defineProps<{
    event: {
        id: number | string
        title: string
        date?: string | null
    }
    map: EventMap
    stands: Stand[]
    backHref?: string
}>();

const query = ref('');
const selectedStandId = ref<Stand['id'] | null>(null);

const normalizedQuery = computed(() => query.value.trim().toLowerCase());

const filteredStands = computed(() => {
    if (!normalizedQuery.value) return props.stands;
    const q = normalizedQuery.value;
    return props.stands.filter((s) => {
        const code = (s.code ?? '').toString().toLowerCase();
        const company = (s.company_name ?? '').toString().toLowerCase();
        return code.includes(q) || company.includes(q);
    });
});

const standsWithCoords = computed(() => {
    return filteredStands.value.filter(
        (s) => typeof s.x_percent === 'number' && typeof s.y_percent === 'number'
    );
});

const selectedStand = computed(() => {
    if (selectedStandId.value == null) return null;
    return props.stands.find((s) => s.id === selectedStandId.value) ?? null;
});

const selectedStandWithCoords = computed(() => {
    if (selectedStandId.value == null) return null;

    const s = props.stands.find((x) => x.id === selectedStandId.value) ?? null;
    if (!s) return null;

    if (typeof s.x_percent !== 'number' || typeof s.y_percent !== 'number') return null;

    return s;
});

function selectStand(id: Stand['id']) {
    selectedStandId.value = id;
}

function clearSelection() {
    selectedStandId.value = null;
}

function clamp(n: number, min: number, max: number) {
    return Math.max(min, Math.min(max, n));
}
</script>

<template>
    <Head :title="`${event.title} - Plattegrond`" />

    <AppHeader />

    <section class="mx-auto max-w-10/12 px-4 py-10 md:px-8">
        <div class="flex flex-col gap-6">
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
                        Zoek je stand op de plattegrond. Klik op een markering of selecteer een stand uit de lijst.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="backHref"
                        :href="backHref"
                        class="inline-flex items-center justify-center rounded-full border border-stroke bg-white px-4 py-2 text-sm font-medium text-black hover:bg-gray-50 dark:border-strokedark dark:bg-blacksection dark:text-white dark:hover:bg-blacksection/70"
                    >
                        Terug
                    </Link>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-12">
                <!-- Map -->
                <div class="lg:col-span-8">
                    <div
                        class="relative overflow-hidden rounded-2xl border border-stroke bg-white dark:border-strokedark dark:bg-blacksection"
                    >
                        <div class="relative">
                            <!-- zoom layer -->
                            <div class="relative">
                                <img
                                    :src="map.image_url"
                                    :alt="`${event.title} map`"
                                    class="block h-auto w-full select-none"
                                    draggable="false"
                                />

                                <!-- Selected stand highlight (large marker) -->
                                <div
                                    v-if="selectedStandWithCoords"
                                    class="absolute z-10"
                                    :style="{
                                        left: `${selectedStandWithCoords.x_percent}%`,
                                        top: `${selectedStandWithCoords.y_percent}%`,
                                        transform: 'translate(-50%, -50%)',
                                    }"
                                >
                                    <div class="relative">
                                        <div class="h-14 w-14 rounded-full border-4 border-blue-500 bg-blue-500/20"></div>
                                        <div class="absolute inset-0 h-14 w-14 rounded-full border-4 border-blue-500 animate-ping"></div>
                                        <div
                                            class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white shadow"
                                        >
                                            {{ selectedStandWithCoords.code }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="standsWithCoords.length === 0"
                                    class="absolute left-4 top-4 rounded-xl border border-stroke bg-white/90 px-3 py-2 text-xs text-black shadow-sm backdrop-blur dark:border-strokedark dark:bg-blacksection/90 dark:text-white"
                                >
                                    Er zijn nog geen markeringen ingesteld voor dit evenement. Gebruik de lijst rechts.
                                </div>

                            </div>

                            <!-- empty state when filtering hides all -->
                            <div
                                v-if="filteredStands.length === 0"
                                class="absolute inset-0 flex items-center justify-center px-6 text-center"
                            >
                                <div class="max-w-md rounded-2xl border border-stroke bg-white/90 p-5 text-sm text-black shadow-sm backdrop-blur dark:border-strokedark dark:bg-blacksection/90 dark:text-white">
                                    Geen stands gevonden die overeenkomen met je zoekopdracht.
                                </div>
                            </div>
                        </div>

                        <!-- Selected stand card -->
                        <div v-if="selectedStand" class="border-t border-stroke p-4 dark:border-strokedark">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-primary/10 px-2 py-1 text-xs font-semibold text-primary">
                      Stand {{ selectedStand.code }}
                    </span>
                                    </div>
                                    <div class="mt-1 truncate text-sm font-medium text-black dark:text-white">
                                        {{ selectedStand.company_name ?? 'Company not set' }}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-full border border-stroke bg-white px-4 py-2 text-sm font-medium text-black hover:bg-gray-50 dark:border-strokedark dark:bg-blacksection dark:text-white dark:hover:bg-blacksection/70"
                                    @click="clearSelection"
                                >
                                    Sluiten
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Sidebar -->
                <aside class="lg:col-span-4">
                    <div class="rounded-2xl border border-stroke bg-white p-4 dark:border-strokedark dark:bg-blacksection">
                        <div class="flex items-center gap-2">
                            <Search class="h-4 w-4 text-muted-foreground" />
                            <input
                                v-model="query"
                                type="text"
                                placeholder="Zoek op standnummer of bedrijfsnaam…"
                                class="w-full bg-transparent text-sm text-black placeholder:text-muted-foreground focus:outline-none dark:text-white"
                            />
                        </div>

                        <div class="mt-4 flex items-center justify-between text-xs text-muted-foreground">
                            <span>{{ filteredStands.length }} standplaatsen</span>
                            <button
                                v-if="query"
                                type="button"
                                class="hover:underline"
                                @click="query = ''"
                            >
                                Wissen
                            </button>
                        </div>

                        <div class="mt-4 max-h-[520px] overflow-auto pr-1">
                            <button
                                v-for="stand in filteredStands"
                                :key="stand.id"
                                type="button"
                                @click="selectStand(stand.id)"
                                class="group flex w-full items-start justify-between gap-3 rounded-xl border border-transparent px-3 py-2 text-left hover:border-stroke hover:bg-gray-50 dark:hover:border-strokedark dark:hover:bg-blacksection/70"
                                :class="selectedStandId === stand.id ? 'border-stroke bg-gray-50 dark:border-strokedark dark:bg-blacksection/70' : ''"
                            >
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-black/5 px-2 py-0.5 text-xs font-semibold text-black dark:bg-white/10 dark:text-white">
                      {{ stand.code }}
                    </span>
                                    </div>
                                    <div class="mt-1 truncate text-sm font-medium text-black dark:text-white">
                                        {{ stand.company_name ?? 'Company not set' }}
                                    </div>
                                </div>

                                <MapPin class="mt-1 h-4 w-4 shrink-0 text-muted-foreground group-hover:text-black dark:group-hover:text-white" />
                            </button>
                        </div>
                    </div>

                </aside>
            </div>
        </div>
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
