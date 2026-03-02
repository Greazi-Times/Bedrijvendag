<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PhBook, PhHandshake, PhMicrophone, PhShareNetwork, PhArrowDown, PhWine } from '@phosphor-icons/vue';
import { Users, Compass, LogIn, Play, X, CheckCircle2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';


import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';

type EventCard = {
    id: number;
    name: string;
    date: string | null;
    description: string | null; // RichEditor stores HTML
    image_url: string | null;
};

type PartnerCard = {
    id: number;
    name: string;
    url: string | null;
    image_url: string | null;
};

const props = defineProps<{
    recentEvents: EventCard[];
    highlightEvent: EventCard | null;
    closingBorrelCount: number;
    partners: PartnerCard[];
}>();

const isVideoOpen = ref(false);

// Replace this with your real YouTube video id later.
const youtubeVideoId = ref('yMBxJQk7gbg');

// Used to force iframe remount to stop playback on close.
const videoInstanceKey = ref(0);

const descriptionPreview = (value: string | null) => {
    if (!value) return '';

    // Convert RichEditor HTML to plain text for short previews (keeps cards tidy)
    try {
        const doc = new DOMParser().parseFromString(value, 'text/html');
        return (doc.body.textContent ?? '').trim();
    } catch {
        return value.replace(/<[^>]*>/g, '').trim();
    }
};

const latestEditions = computed(() => {
    const toTime = (value: string | null) => {
        if (!value) return null;
        const t = Date.parse(value);
        return Number.isFinite(t) ? t : null;
    };

    const now = Date.now();

    const all = (props.recentEvents ?? []).map((e) => ({ ...e, __t: toTime(e.date) })).filter((e) => e.__t !== null);

    // 1) Pick the next upcoming event (soonest in the future)
    const upcoming = all.filter((e) => (e.__t as number) >= now).sort((a, b) => (a.__t as number) - (b.__t as number))[0];

    // 2) Fill the rest with the most recent past events
    const past = all.filter((e) => (e.__t as number) < now).sort((a, b) => (b.__t as number) - (a.__t as number));

    const picked: typeof all = [];
    if (upcoming) picked.push(upcoming);

    for (const e of past) {
        if (picked.length >= 3) break;
        if (picked.some((p) => p.id === e.id)) continue;
        picked.push(e);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    return picked.map(({ __t, ...e }) => e);
});

const youtubeEmbedUrl = computed(() => {
    const id = youtubeVideoId.value.trim();
    return `https://www.youtube-nocookie.com/embed/${id}?autoplay=1&rel=0&modestbranding=1`;
});

const openVideo = () => {
    isVideoOpen.value = true;
    document.body.style.overflow = 'hidden';
};

const closeVideo = () => {
    isVideoOpen.value = false;
    videoInstanceKey.value += 1;
    document.body.style.overflow = '';
};

const onKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && isVideoOpen.value) closeVideo();
};

window.addEventListener('keydown', onKeydown);

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
    if (borrelSuccessTimeout) window.clearTimeout(borrelSuccessTimeout);
});

// Borrel form logic

const borrelForm = useForm({
    name: '',
    email: '',
    event_id: props.highlightEvent?.id ?? null,
});

const showBorrelSuccess = ref(false);
let borrelSuccessTimeout: number | undefined;

const triggerBorrelSuccess = () => {
    showBorrelSuccess.value = true;

    if (borrelSuccessTimeout) window.clearTimeout(borrelSuccessTimeout);

    borrelSuccessTimeout = window.setTimeout(() => {
        showBorrelSuccess.value = false;
    }, 7000); // 7 seconds
};
</script>

<template>
    <Head title="Home">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <AppHeader class="sticky top-0 z-50" />

    <header class="bg-background px-6 pt-14 pb-16 lg:px-16">
        <div class="mx-auto max-w-7xl">
            <!-- Top: copy + image -->
            <div class="grid items-center gap-10 lg:grid-cols-12">
                <div class="lg:col-span-7">
                    <p class="inline-flex items-center rounded-full bg-accent px-4 py-2 text-xs font-semibold tracking-wide text-accent-foreground ring-1 ring-border">
                        VOOR STUDENTEN & DOOR STUDENTEN
                    </p>

                    <h1 class="mt-6 text-4xl font-semibold tracking-tight text-foreground sm:text-5xl lg:text-6xl">DE ATIx BEDRIJVENDAG</h1>

                    <p class="mt-4 max-w-xl text-base leading-relaxed text-muted-foreground sm:text-lg">Ontmoet bedrijven, bouw je netwerk, en meld je aan voor de borrel.</p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Link
                            class="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                            href="/over-ons"
                        >
                            Meer weten
                        </Link>

                        <Link
                            class="inline-flex items-center justify-center rounded-xl bg-background px-6 py-3 text-sm font-semibold text-foreground shadow-sm ring-1 ring-border transition hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                            href="/edities"
                        >
                            Vorige edities
                        </Link>

                        <Link
                            href="#borrel"
                            class="inline-flex items-center justify-center rounded-xl bg-secondary px-6 py-3 text-sm font-semibold text-secondary-foreground shadow-sm ring-1 ring-secondary/25 transition hover:bg-secondary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                        >
                            Aanmelden borrel
                        </Link>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-muted-foreground">
                        <div class="inline-flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-primary"></span>
                            Volgende editie:
                            {{ props.highlightEvent?.date ?? 'Binnenkort' }}
                        </div>
                        <div class="inline-flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-secondary"></span>
                            Borrel aanmeldingen: {{ props.closingBorrelCount }}
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="relative mx-auto w-full max-w-md">
                        <!-- Decorative shapes (token based) -->
                        <div class="pointer-events-none absolute -top-10 -right-6 h-72 w-72 rounded-full bg-secondary/20"></div>
                        <div class="pointer-events-none absolute top-24 -left-8 h-28 w-28 rounded-[2.25rem] bg-primary/25"></div>
                        <div class="pointer-events-none absolute right-10 -bottom-8 h-20 w-20 rounded-full bg-accent"></div>

                        <!-- Image container -->
                        <div class="relative overflow-hidden rounded-[999px] shadow-lg ring-1 ring-border">
                            <img src="/images/hero.png" alt="ATIx Bedrijvendag" class="h-[420px] w-full object-cover object-center" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom: 3 scopes/features -->
            <div class="mt-20 grid gap-14 md:grid-cols-3 md:gap-16">
                <div class="flex items-start gap-6">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-primary/15">
                        <Users class="h-9 w-9 text-primary" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-semibold tracking-tight text-foreground">Contact</h3>
                        <p class="mt-3 max-w-sm text-base leading-relaxed text-muted-foreground">
                            Spreek studenten van Mechatronica, Werktuigbouwkunde, (Technische) Informatica, Elektrotechniek, Business IT & Management, Technische Bedrijfskunde en
                            Industrial Engineering & Management
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-6">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-secondary/15">
                        <Compass class="h-9 w-9 text-secondary" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-semibold tracking-tight text-foreground">Oriënteren</h3>
                        <p class="mt-3 max-w-sm text-base leading-relaxed text-muted-foreground">
                            Oriënteer bij bedrijven van jouw opleiding en neem de volgende stap in het zoeken van een stageplaats
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-6">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-chart-2/15">
                        <LogIn class="h-9 w-9 text-chart-2" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-semibold tracking-tight text-foreground">Vrije Inloop</h3>
                        <p class="mt-3 max-w-sm text-base leading-relaxed text-muted-foreground">
                            Studenten lopen vrij rond over de bedrijvendag, maak een praatje en kom meer te weten over het bedrijf
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- What is ATIx Bedrijvendag -->
    <section class="bg-background px-6 py-20 lg:px-16">
        <div class="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-12">
            <!-- Left: image collage -->
            <div class="lg:col-span-6">
                <div class="relative mx-auto max-w-xl">
                    <!-- Decorative shapes -->
                    <div class="pointer-events-none absolute -top-10 -left-8 h-20 w-20 rounded-full bg-secondary/25"></div>
                    <div class="pointer-events-none absolute -top-2 right-10 h-10 w-10 rounded-full bg-accent"></div>
                    <div class="pointer-events-none absolute -bottom-10 left-10 h-32 w-32 rounded-t-full bg-primary/25"></div>
                    <div class="pointer-events-none absolute top-6 -left-3 flex flex-col gap-1">
                        <span class="h-5 w-0.5 rounded bg-destructive/60"></span>
                        <span class="h-5 w-0.5 rounded bg-destructive/60"></span>
                        <span class="h-5 w-0.5 rounded bg-destructive/60"></span>
                    </div>

                    <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                        <!-- Left column: 2 stacked images -->
                        <div class="flex flex-col gap-6 sm:w-5/12">
                            <div class="overflow-hidden rounded-3xl shadow-sm ring-1 ring-border">
                                <div class="aspect-[3/4] w-full">
                                    <img src="/images/info-1.jpg" alt="ATIx Bedrijvendag sfeer" class="h-full w-full object-cover" />
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-3xl shadow-sm ring-1 ring-border">
                                <div class="aspect-[3/4] w-full">
                                    <img src="/images/info-2.jpg" alt="ATIx Bedrijvendag studenten" class="h-full w-full object-cover" />
                                </div>
                            </div>
                        </div>

                        <!-- Right: taller image -->
                        <div class="sm:w-7/12">
                            <div class="overflow-hidden rounded-3xl shadow-sm ring-1 ring-border">
                                <div class="aspect-[3/4] w-full">
                                    <img src="/images/info-3.jpg" alt="ATIx Bedrijvendag bedrijven" class="h-full w-full object-cover object-center" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: copy + play button -->
            <div class="lg:col-span-6">
                <p class="text-sm font-semibold text-primary">Wat is ATIx Bedrijvendag</p>

                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">Bedrijven ontmoeten, vragen stellen, en direct contact leggen</h2>

                <p class="mt-4 max-w-xl text-base leading-relaxed text-muted-foreground">
                    ATIx Bedrijvendag brengt studenten en bedrijven samen op 1 plek. Je loopt langs stands, spreekt recruiters en engineers, en krijgt een beeld van stage,
                    afstuderen en startersfuncties.
                </p>

                <ul class="mt-6 space-y-2 text-sm text-muted-foreground">
                    <li class="flex gap-3">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary"></span>
                        Gericht op technische opleidingen binnen ATIx
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary"></span>
                        Snel oriënteren op bedrijven en functies
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary"></span>
                        Vrije inloop met borrel na afloop
                    </li>
                </ul>

                <div class="mt-8 flex items-center gap-4">
                    <button
                        type="button"
                        class="inline-flex items-center gap-4 rounded-xl bg-background px-3 py-2 text-sm font-semibold text-foreground transition hover:bg-accent hover:text-accent-foreground hover:ring-1 hover:ring-border focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                        @click="openVideo"
                    >
                        <span class="relative inline-flex h-11 w-11 items-center justify-center">
                            <!-- Ping effect -->
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary/40"></span>

                            <!-- Solid circle -->
                            <span class="relative inline-flex h-11 w-11 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-sm">
                                <Play class="h-5 w-5" />
                            </span>
                        </span>
                        Bekijk hoe het werkt
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA under Latest editions -->
    <section class="relative overflow-hidden bg-primary px-6 py-16 lg:px-16 lg:py-20">
        <img alt="Bg Shape" loading="lazy" width="1660" height="337" decoding="async" class="pointer-events-none absolute right-0 bottom-0" src="/images/shape/shape-16.svg" />

        <div class="relative z-10 mx-auto max-w-7xl">
            <div class="flex flex-wrap gap-8 md:flex-nowrap md:items-center md:justify-between">
                <div class="lg:w-1/2">
                    <h2 class="mb-4 text-3xl font-semibold text-white lg:text-4xl">Meld je aan voor de volgende editie</h2>
                    <p class="text-white/90">Krijg updates over deelnemende bedrijven, programma en locatie. Of bekijk eerdere edities.</p>
                </div>

                <div class="shrink-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            href="#borrel"
                            class="inline-flex items-center justify-center rounded-full bg-white px-7.5 py-3 text-sm font-semibold text-black transition hover:shadow-xl focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:outline-none"
                        >
                            Aanmelden borrel
                        </Link>
                        <Link
                            href="/Users/nowey/Herd/Bedrijvendag2/resources/js/pages/Events"
                            class="inline-flex items-center justify-center rounded-full bg-white/10 px-7.5 py-3 text-sm font-semibold text-white ring-1 ring-white/25 transition hover:bg-white/15 focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:outline-none"
                        >
                            Vorige edities
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our values -->
    <section class="bg-background px-6 py-20 lg:px-16">
        <div class="mx-auto max-w-6xl">
            <h2 class="text-center text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">Onze Waarden</h2>

            <div class="mt-12 grid grid-cols-1 gap-y-0 md:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg px-8 py-12 transition duration-300 ease-in-out hover:bg-accent/20 hover:shadow-xl">
                    <PhArrowDown :size="50" weight="duotone" class="text-primary" />
                    <h3 class="mt-11 mb-5 text-2xl font-semibold tracking-tight text-foreground">Laagdrempelig</h3>
                    <p class="text-base leading-relaxed text-muted-foreground">
                        Een toegankelijke en informele sfeer waar studenten zich op hun gemak voelen om in contact te komen met bedrijven.
                    </p>
                </div>

                <div class="rounded-lg px-8 py-12 transition duration-300 ease-in-out hover:bg-accent/20 hover:shadow-xl">
                    <PhBook :size="50" weight="duotone" class="text-primary" />
                    <h3 class="mt-11 mb-5 text-2xl font-semibold tracking-tight text-foreground">Toekomstgericht</h3>
                    <p class="text-base leading-relaxed text-muted-foreground">
                        Focus op het verbinden van studenten met bedrijven die stageplaatsen en carrièremogelijkheden bieden.
                    </p>
                </div>

                <div class="rounded-lg px-8 py-12 transition duration-300 ease-in-out hover:bg-accent/20 hover:shadow-xl">
                    <PhWine :size="50" weight="duotone" class="text-primary" />
                    <h3 class="mt-11 mb-5 text-2xl font-semibold tracking-tight text-foreground">Borrelen</h3>
                    <p class="text-base leading-relaxed text-muted-foreground">Sluit de dag af met een gezellige borrel en netwerk in een informele setting.</p>
                </div>

                <div class="rounded-lg px-8 py-12 transition duration-300 ease-in-out hover:bg-accent/20 hover:shadow-xl">
                    <PhShareNetwork :size="50" weight="duotone" class="text-primary" />
                    <h3 class="mt-11 mb-5 text-2xl font-semibold tracking-tight text-foreground">Netwerken</h3>
                    <p class="text-base leading-relaxed text-muted-foreground">Breid je netwerk uit en leg waardevolle contacten voor je toekomstige carrière.</p>
                </div>

                <div class="rounded-lg px-8 py-12 transition duration-300 ease-in-out hover:bg-accent/20 hover:shadow-xl">
                    <PhMicrophone :size="50" weight="duotone" class="text-primary" />
                    <h3 class="mt-11 mb-5 text-2xl font-semibold tracking-tight text-foreground">Inspirerend</h3>
                    <p class="text-base leading-relaxed text-muted-foreground">Laat je inspireren door de verhalen en ervaringen van professionals uit het veld.</p>
                </div>

                <div class="rounded-lg px-8 py-12 transition duration-300 ease-in-out hover:bg-accent/20 hover:shadow-xl">
                    <PhHandshake :size="50" weight="duotone" class="text-primary" />
                    <h3 class="mt-11 mb-5 text-2xl font-semibold tracking-tight text-foreground">Samenwerking</h3>
                    <p class="text-base leading-relaxed text-muted-foreground">Werk samen met medestudenten en professionals om nieuwe kansen te ontdekken.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Borrel Aanmelden Form -->
    <section
        id="borrel"
        class="relative overflow-hidden bg-background px-6 py-20 lg:px-16"
        style="background-image: url('/images/shape/shape-12.svg'); background-repeat: no-repeat; background-position: left bottom  ; background-size: 900px auto;"
    >

        <div class="relative z-10 mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold text-primary">Borrel</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">Meld je aan voor de borrel</h2>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">Laat je e-mail achter. Dan sturen we je de details.</p>

                <div class="mt-6 inline-flex items-center gap-2 rounded-full bg-accent px-4 py-2 text-xs font-semibold text-accent-foreground ring-1 ring-border">
                    <span class="h-2 w-2 rounded-full bg-secondary"></span>
                    Al aangemeld: {{ props.closingBorrelCount }}
                </div>
            </div>

            <div class="mx-auto mt-12 max-w-2xl">
                <div class="relative overflow-hidden rounded-2xl bg-background p-6 shadow-sm ring-1 ring-border sm:p-8">
                    <!-- Card decorations -->
                    <div class="pointer-events-none absolute -top-10 -right-10 h-32 w-32 rounded-full bg-primary/10"></div>
                    <div class="pointer-events-none absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-secondary/10"></div>

                    <form
                        class="relative grid gap-5"
                        @submit.prevent="
                            borrelForm.post('/borrel-signup', {
                                preserveScroll: true,
                                onSuccess: () => {
                                    triggerBorrelSuccess();
                                    borrelForm.reset('name', 'email');
                                },
                            })
                        "
                    >
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-foreground"> Naam </label>
                            <input
                                v-model="borrelForm.name"
                                type="text"
                                autocomplete="name"
                                class="w-full rounded-xl bg-background px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                                placeholder="Voor- en achternaam"
                            />
                            <p v-if="borrelForm.errors.name" class="mt-2 text-sm text-destructive">
                                {{ borrelForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-foreground"> E-mail </label>
                            <input
                                v-model="borrelForm.email"
                                type="email"
                                autocomplete="email"
                                class="w-full rounded-xl bg-background px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                                placeholder="E-mailadres"
                            />
                            <p v-if="borrelForm.errors.email" class="mt-2 text-sm text-destructive">
                                {{ borrelForm.errors.email }}
                            </p>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="borrelForm.processing"
                            >
                                {{ borrelForm.processing ? 'Bezig...' : 'Aanmelden' }}
                            </button>

                            <p class="text-xs text-muted-foreground">Je ontvangt alleen info over de borrel.</p>
                        </div>
                    </form>

                    <div
                        v-if="showBorrelSuccess"
                        class="mt-6 flex items-center gap-2 rounded-xl bg-emerald-500/15 p-4 text-sm text-emerald-900 ring-1 ring-emerald-500/25"
                        role="status"
                        aria-live="polite"
                    >
                        <CheckCircle2 class="h-5 w-5 shrink-0 text-emerald-600" />
                        <span>Je bent aangemeld. Tot bij de borrel.</span>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- Partners -->
    <section id="partners" class="bg-accent/50 px-6 py-16 lg:px-16">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold text-primary">Partners</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">Met dank aan onze partners</h2>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">Deze partijen maken ATIx Bedrijvendag mogelijk.</p>
            </div>

            <div class="mt-10 rounded-2xl bg-accent/10 p-6 ring-1 ring-border sm:p-8">
                <div class="flex flex-wrap items-center justify-center gap-x-14 gap-y-10">
                    <div v-for="p in props.partners" :key="p.id" class="group flex basis-1/2 items-center justify-center sm:basis-1/3 lg:basis-1/4">
                        <component
                            :is="p.url ? 'a' : 'div'"
                            :href="p.url ?? undefined"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center justify-center"
                        >
                            <img
                                v-if="p.image_url"
                                :src="p.image_url"
                                :alt="p.name"
                                class="max-h-16 w-full max-w-[220px] object-contain opacity-80 grayscale transition duration-200 group-hover:opacity-100 group-hover:grayscale-0"
                                loading="lazy"
                                decoding="async"
                            />
                            <div v-else class="text-sm text-muted-foreground">Geen logo</div>
                        </component>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest editions (3 most recent past events) -->
    <section
        class="relative overflow-hidden bg-background px-6 py-20 lg:px-16"
        style="background-image: url('/images/shape/shape-13.svg'); background-repeat: no-repeat; background-position: right top; background-size: 1500px auto;"
    >

        <div class="relative mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold text-primary">Laatste edities</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">Bekijk de 3 meest recente edities</h2>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">De eerstvolgende editie plus de meest recente edities.</p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                <article v-for="e in latestEditions" :key="e.id" class="group overflow-hidden rounded-2xl bg-background shadow-sm ring-1 ring-border transition hover:shadow-xl">
                    <div class="relative aspect-[16/10] w-full bg-accent/20">
                        <img v-if="e.image_url" :src="e.image_url" :alt="e.name" class="h-full w-full object-cover" />
                        <div v-else class="flex h-full w-full items-center justify-center text-sm text-muted-foreground">Geen afbeelding</div>

                        <div
                            class="absolute top-4 left-4 inline-flex items-center rounded-full bg-background/90 px-3 py-1 text-xs font-semibold text-foreground ring-1 ring-border"
                        >
                            {{ e.date ?? 'Onbekende datum' }}
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-semibold tracking-tight text-foreground">
                            {{ e.name }}
                        </h3>

                        <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-muted-foreground">
                            {{ descriptionPreview(e.description) }}
                        </p>

                        <div class="mt-6">
                            <Link
                                :href="`/edities/${e.id}`"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                            >
                                Bekijk editie
                            </Link>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <AppFooter />

    <Teleport to="body">
        <div v-if="isVideoOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4" aria-modal="true" role="dialog" @click.self="closeVideo">
            <div class="absolute inset-0 bg-black/60"></div>

            <div class="relative z-[101] w-full max-w-4xl overflow-hidden rounded-2xl bg-background shadow-xl ring-1 ring-border">
                <div class="flex items-center justify-between gap-4 border-b border-border px-4 py-3">
                    <div class="text-sm font-semibold text-foreground">Video</div>
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground ring-1 ring-border transition hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                        @click="closeVideo"
                        aria-label="Sluiten"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="relative aspect-video w-full bg-black">
                    <iframe
                        :key="videoInstanceKey"
                        class="absolute inset-0 h-full w-full"
                        :src="youtubeEmbedUrl"
                        title="ATIx Bedrijvendag video"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                        referrerpolicy="strict-origin-when-cross-origin"
                    ></iframe>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
@keyframes shake {
    0%,
    10% {
        transform: rotate(0deg);
    }
    15% {
        transform: rotate(-5deg);
    }
    20% {
        transform: rotate(5deg);
    }
    25% {
        transform: rotate(-5deg);
    }
    30% {
        transform: rotate(5deg);
    }
    35%,
    100% {
        transform: rotate(0deg);
    }
}

.animate-shake {
    animation: shake 3s infinite ease-in-out;
}
/* Basic styling for RichEditor HTML when rendered with v-html */
:deep(.rich-content p) {
    margin: 0.75rem 0;
}

:deep(.rich-content ul),
:deep(.rich-content ol) {
    margin: 0.75rem 0;
    padding-left: 1.25rem;
}

:deep(.rich-content a) {
    text-decoration: underline;
}
</style>
