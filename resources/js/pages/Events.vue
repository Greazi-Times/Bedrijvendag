<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';

type EventItem = {
    id: number;
    title: string;
    starts_at: string | null;
    ends_at: string | null;
    location: string | null;
    short_description: string | null;

    // Rich text HTML coming from the backend
    description_html: string | null;

    // Header image for the edition
    header_image_url: string | null;

    // Full edition page (route will be implemented later)
    edition_url: string | null;

    // Optional photo gallery
    gallery_url: string | null;

    // Map (kept for later, not shown now)
    map_url?: string | null;
};

type Props = {
    upcoming: EventItem[];
    past: EventItem[];
};

const props = defineProps<Props>();

const selected = ref<EventItem | null>(null);
const isModalOpen = computed(() => selected.value !== null);

const featuredUpcoming = computed(() => props.upcoming[0] ?? null);
const otherUpcoming = computed(() => props.upcoming.slice(1));

function closeModal() {
    selected.value = null;
}

function openModal(event: EventItem) {
    selected.value = event;
}

function formatDateRange(startsAt: string | null, endsAt: string | null) {
    if (!startsAt && !endsAt) return 'Onbekende datum';

    const fmt = new Intl.DateTimeFormat('nl-NL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });

    const start = startsAt ? fmt.format(new Date(startsAt)) : null;
    const end = endsAt ? fmt.format(new Date(endsAt)) : null;

    if (start && end && start !== end) return `${start} t/m ${end}`;
    return start ?? end ?? 'Onbekende datum';
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') closeModal();
}

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <AppHeader class="sticky top-0 z-50" />

    <main class="relative bg-background">
        <section class="relative overflow-hidden bg-background px-6 py-16 sm:py-20 lg:px-16">
            <div class="pointer-events-none absolute -top-24 -right-24 h-80 w-80 rounded-full bg-secondary/20" />
            <div class="pointer-events-none absolute top-28 -left-20 h-40 w-40 rounded-[2.75rem] bg-primary/20" />
            <div class="pointer-events-none absolute right-24 bottom-10 h-16 w-16 rounded-full bg-accent" />
            <div class="relative mx-auto w-full max-w-7xl">
                <div class="flex flex-col gap-3">
                    <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Events</h1>
                    <p class="max-w-2xl text-base leading-relaxed text-muted-foreground">
                        Bekijk alle aankomende events en eerdere events. Klik op een event voor details.
                    </p>
                </div>

                <div class="mt-10 space-y-10">
                    <!-- Featured upcoming -->
                    <section>
                        <div class="flex items-baseline justify-between">
                            <h2 class="text-xl font-semibold">Aankomend</h2>
                            <span class="text-sm text-muted-foreground">{{ props.upcoming.length }} event(s)</span>
                        </div>

                        <div v-if="featuredUpcoming" class="mt-4">
                            <button
                                type="button"
                                class="group w-full overflow-hidden rounded-3xl bg-background text-left shadow-sm ring-1 ring-border transition hover:shadow-xl"
                                @click="openModal(featuredUpcoming)"
                            >
                                <div class="grid grid-cols-1 gap-0 lg:grid-cols-5">
                                    <div class="lg:col-span-3">
                                        <div class="relative">
                                            <div class="h-56 w-full bg-accent/20 sm:h-72">
                                                <img
                                                    v-if="featuredUpcoming.header_image_url"
                                                    :src="featuredUpcoming.header_image_url"
                                                    :alt="featuredUpcoming.title"
                                                    class="h-full w-full object-cover"
                                                />
                                            </div>
                                            <div class="absolute left-5 top-5 inline-flex items-center gap-2 rounded-full bg-background/90 px-3 py-1 text-xs font-semibold text-foreground ring-1 ring-border">
                                                <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500" />
                                                Volgende editie
                                            </div>
                                        </div>
                                    </div>

                                    <div class="lg:col-span-2">
                                        <div class="flex h-full flex-col justify-between p-6">
                                            <div>
                                                <p class="text-sm text-muted-foreground">
                                                    {{ formatDateRange(featuredUpcoming.starts_at, featuredUpcoming.ends_at) }}
                                                    <span v-if="featuredUpcoming.location">• {{ featuredUpcoming.location }}</span>
                                                </p>
                                                <h3 class="mt-2 text-2xl font-semibold tracking-tight">
                                                    {{ featuredUpcoming.title }}
                                                </h3>
                                                <p v-if="featuredUpcoming.short_description" class="mt-3 line-clamp-3 text-sm leading-relaxed text-muted-foreground">
                                                    {{ featuredUpcoming.short_description }}
                                                </p>
                                            </div>

                                            <div class="mt-6 flex items-center justify-between">
                                                <span class="text-sm text-muted-foreground">Klik voor details</span>
                                                <span class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition group-hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none">
                                                    Details
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </button>

                            <div v-if="otherUpcoming.length" class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <button
                                    v-for="event in otherUpcoming"
                                    :key="event.id"
                                    type="button"
                                    class="group overflow-hidden rounded-2xl bg-background text-left shadow-sm ring-1 ring-border transition hover:shadow-xl"
                                    @click="openModal(event)"
                                >
                                    <div class="h-40 w-full bg-accent/20">
                                        <img
                                            v-if="event.header_image_url"
                                            :src="event.header_image_url"
                                            :alt="event.title"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div class="p-5">
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatDateRange(event.starts_at, event.ends_at) }}
                                            <span v-if="event.location">• {{ event.location }}</span>
                                        </p>
                                        <h3 class="mt-2 line-clamp-2 text-base font-semibold">{{ event.title }}</h3>
                                        <p v-if="event.short_description" class="mt-2 line-clamp-2 text-sm leading-relaxed text-muted-foreground">
                                            {{ event.short_description }}
                                        </p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div
                            v-else
                            class="mt-4 rounded-2xl border border-dashed border-border bg-background/40 px-5 py-6 text-sm text-muted-foreground"
                        >
                            Geen aankomend event.
                        </div>
                    </section>

                    <!-- Past editions -->
                    <section>
                        <div class="flex items-baseline justify-between">
                            <h2 class="text-xl font-semibold">Eerdere edities</h2>
                            <span class="text-sm text-muted-foreground">{{ props.past.length }} editie(s)</span>
                        </div>

                        <div v-if="props.past.length" class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <button
                                v-for="event in props.past"
                                :key="event.id"
                                type="button"
                                class="group overflow-hidden rounded-2xl bg-background text-left shadow-sm ring-1 ring-border transition hover:shadow-xl"
                                @click="openModal(event)"
                            >
                                <div class="h-44 w-full bg-accent/20 sm:h-48">
                                    <img
                                        v-if="event.header_image_url"
                                        :src="event.header_image_url"
                                        :alt="event.title"
                                        class="h-full w-full object-cover"
                                    />
                                </div>

                                <div class="p-5">
                                    <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="inline-flex h-2 w-2 rounded-full bg-gray-400" />
                                            Eerder
                                        </span>
                                        <span class="truncate">
                                            {{ formatDateRange(event.starts_at, event.ends_at) }}
                                            <span v-if="event.location">• {{ event.location }}</span>
                                        </span>
                                    </div>

                                    <h3 class="mt-2 line-clamp-2 text-base font-semibold">
                                        {{ event.title }}
                                    </h3>

                                    <p v-if="event.short_description" class="mt-2 line-clamp-2 text-sm leading-relaxed text-muted-foreground">
                                        {{ event.short_description }}
                                    </p>

                                    <p v-else class="mt-2 line-clamp-2 text-sm text-muted-foreground">
                                        Klik om meer te lezen.
                                    </p>

                                    <div class="mt-4 flex items-center justify-between">
                                        <span class="text-sm text-muted-foreground">Details</span>
                                        <span class="text-sm font-medium text-foreground">Open</span>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <div
                            v-else
                            class="mt-4 rounded-2xl border border-dashed border-border bg-background/40 px-5 py-6 text-sm text-muted-foreground"
                        >
                            Nog geen eerdere events.
                        </div>
                    </section>
                </div>
            </div>
        </section>

        <!-- Modal -->
        <teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 bg-black/60"
                aria-label="Sluiten"
                @click="closeModal"
            >
                <div class="absolute inset-0 flex items-center justify-center px-4 py-8">
                    <div
                        class="w-full max-w-3xl overflow-hidden rounded-3xl bg-background shadow-xl ring-1 ring-border"
                        role="dialog"
                        aria-modal="true"
                        @click.stop
                    >
                        <div class="flex items-start justify-between gap-4 border-b border-border px-6 py-5">
                            <div class="min-w-0">
                                <p class="text-sm text-muted-foreground">
                                    {{ formatDateRange(selected?.starts_at ?? null, selected?.ends_at ?? null) }}
                                    <span v-if="selected?.location">• {{ selected.location }}</span>
                                </p>
                                <h3 class="mt-1 truncate text-xl font-semibold">{{ selected?.title }}</h3>
                            </div>
                        </div>

                        <div class="px-6 py-6">
                            <div class="max-w-none">
                                <div v-if="selected?.header_image_url" class="overflow-hidden rounded-2xl ring-1 ring-border">
                                    <img
                                        :src="selected.header_image_url"
                                        :alt="selected.title"
                                        class="h-56 w-full object-cover sm:h-64"
                                    />
                                </div>

                                <div class="mt-4">
                                    <p class="text-sm font-semibold text-foreground">Beschrijving</p>

                                    <div
                                        v-if="selected?.description_html"
                                        class="prose prose-sm mt-2 max-w-none dark:prose-invert"
                                        v-html="selected.description_html"
                                    />

                                    <p v-else class="mt-2 text-sm text-muted-foreground">Geen beschrijving.</p>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-border px-6 py-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-xs text-muted-foreground">
                                    Bekijk de volledige editie of de fotogalerij.
                                </p>

                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    <a
                                        v-if="selected?.edition_url"
                                        :href="selected.edition_url"
                                        class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                    >
                                        Bekijk volledige editie
                                    </a>

                                    <a
                                        v-if="selected?.gallery_url"
                                        :href="selected.gallery_url"
                                        target="_blank"
                                        rel="noreferrer"
                                        class="inline-flex items-center justify-center rounded-xl bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm ring-1 ring-border transition hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                    >
                                        Bekijk foto's
                                    </a>

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm ring-1 ring-border transition hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                        @click="closeModal"
                                    >
                                        Sluiten
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </teleport>
    </main>

    <AppFooter />
</template>
