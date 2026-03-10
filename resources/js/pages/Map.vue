<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Search } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';

type Stand = {
    id: number | string
    code: string // stand number
    company_name?: string | null
    company_logo?: string | null
    company_description?: string | null
    company_website_url?: string | null
    company_educations?: string[] | null
    company_sectors?: string[] | null
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
const selectedCompany = ref<Stand | null>(null);
const mapImageRef = ref<HTMLImageElement | null>(null);
const mapImageHeight = ref(0);

const normalizedQuery = computed(() => query.value.trim().toLowerCase());

const filteredStands = computed(() => {
    const q = normalizedQuery.value;

    const list = !q
        ? [...props.stands]
        : props.stands.filter((s) => {
              const code = (s.code ?? '').toString().toLowerCase();
              const company = (s.company_name ?? '').toString().toLowerCase();
              return code.includes(q) || company.includes(q);
          });

    return list.sort((a, b) => {
        const aNum = parseInt(String(a.code).replace(/[^0-9]/g, '')) || 0;
        const bNum = parseInt(String(b.code).replace(/[^0-9]/g, '')) || 0;

        if (aNum !== bNum) return aNum - bNum;

        return String(a.code).localeCompare(String(b.code));
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

const updateMapImageHeight = () => {
    mapImageHeight.value = mapImageRef.value?.clientHeight ?? 0;
};

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
    document.body.style.overflow = '';
};

const onKeydown = (e: KeyboardEvent) => {
    if (e.key !== 'Escape') return;
    if (selectedCompany.value) closeCompany();
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

            <div class="grid items-stretch gap-6 lg:grid-cols-12">
                <!-- Map -->
                <div class="lg:col-span-8">
                    <div
                        class="relative overflow-hidden rounded-2xl border border-stroke bg-white dark:border-strokedark dark:bg-blacksection"
                    >
                        <div class="relative">
                            <!-- zoom layer -->
                            <div class="relative">
                                <img
                                    ref="mapImageRef"
                                    :src="map.image_url"
                                    :alt="`${event.title} map`"
                                    class="block h-auto w-full select-none"
                                    draggable="false"
                                    @load="updateMapImageHeight"
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
                <aside class="lg:col-span-4 lg:h-full">
                    <div
                        class="flex min-h-0 flex-col rounded-2xl border border-stroke bg-white p-4 dark:border-strokedark dark:bg-blacksection"
                        :style="mapImageHeight > 0 ? { height: `${mapImageHeight}px` } : undefined"
                    >
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

                        <div class="mt-4 min-h-0 flex-1 overflow-auto pr-1">
                            <div
                                v-for="(stand, index) in filteredStands"
                                :key="stand.id"
                                class="flex items-center gap-3 rounded-xl border border-transparent px-3 py-2 transition hover:border-stroke hover:bg-gray-50 dark:hover:border-strokedark dark:hover:bg-blacksection/70"
                                :class="selectedStandId === stand.id ? 'border-stroke bg-gray-50 dark:border-strokedark dark:bg-blacksection/70' : ''"
                            >
                                <button
                                    type="button"
                                    class="flex min-w-0 flex-1 items-center gap-3 text-left"
                                    @click="selectStand(stand.id)"
                                >
                                    <div
                                        class="flex h-10 min-w-10 shrink-0 items-center justify-center rounded-full px-2 text-sm font-semibold text-foreground"
                                        :class="index % 2 === 0 ? 'bg-primary/20' : 'bg-secondary/25'"
                                    >
                                        {{ stand.code }}
                                    </div>

                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-stroke bg-white dark:border-strokedark dark:bg-blacksection/70">
                                        <img
                                            v-if="stand.company_logo"
                                            :src="stand.company_logo"
                                            :alt="stand.company_name ?? stand.code"
                                            class="h-full w-full object-contain p-1"
                                        />
                                        <span
                                            v-else
                                            class="text-xs font-medium text-muted-foreground"
                                        >
                                            Logo
                                        </span>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-medium text-black dark:text-white">
                                            {{ stand.company_name ?? 'Company not set' }}
                                        </div>
                                    </div>
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl px-4 py-1.5 text-sm font-semibold text-primary-foreground shadow-sm ring-1 transition focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                    :class="index % 2 === 0
                                        ? 'bg-primary/60 ring-primary/20 hover:bg-primary/80'
                                        : 'bg-secondary/80 ring-secondary/25 hover:bg-secondary'"
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

                <div class="absolute left-1/2 top-1/2 flex max-h-[80vh] w-[calc(100%-2rem)] max-w-3xl -translate-x-1/2 -translate-y-1/2 flex-col overflow-hidden rounded-2xl bg-background shadow-2xl ring-1 ring-border">
                    <div class="flex shrink-0 items-center justify-between gap-4 border-b border-border px-6 py-5">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-muted-foreground">Bedrijf</div>
                            <h2 class="mt-1 truncate text-2xl font-semibold tracking-tight text-foreground">
                                {{ selectedCompany.company_name ?? 'Company not set' }}
                            </h2>
                            <div class="mt-2 inline-flex items-center rounded-full bg-accent px-3 py-1 text-xs font-semibold text-accent-foreground ring-1 ring-border">
                                Stand {{ selectedCompany.code }}
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

                    <div class="flex-1 overflow-y-auto px-6 py-6 overscroll-contain">
                        <div>
                            <div class="text-sm font-semibold text-foreground">Beschrijving</div>
                            <div
                                v-if="selectedCompany.company_description"
                                class="prose prose-sm mt-2 max-w-none text-muted-foreground dark:prose-invert prose-p:my-0 prose-ul:my-0 prose-ol:my-0 prose-li:my-0"
                                v-html="sanitizeHtml(selectedCompany.company_description)"
                            ></div>
                            <p v-else class="mt-2 text-sm text-muted-foreground">Geen beschrijving.</p>

                            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                                <div>
                                    <div class="text-sm font-semibold text-foreground">Opleidingen</div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span
                                            v-for="n in (selectedCompany.company_educations ?? [])"
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

                                <div>
                                    <div class="text-sm font-semibold text-foreground">Sectoren</div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span
                                            v-for="n in (selectedCompany.company_sectors ?? [])"
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
