<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PhBook, PhHandshake, PhMicrophone, PhShareNetwork, PhArrowDown, PhWine } from '@phosphor-icons/vue';
import { Users, Compass, LogIn, Play, X, CheckCircle2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { useTranslations } from '@/i18n';

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
    logo_url: string | null;
};

type HomeImages = {
    hero: string;
    infoFirst: string;
    infoSecond: string;
    infoThird: string;
};

type BorrelStatus = 'open' | 'today' | 'none';

const props = defineProps<{
    recentEvents: EventCard[];
    highlightEvent: EventCard | null;
    borrelEvent: EventCard | null;
    borrelStatus: BorrelStatus;
    closingBorrelCount: number;
    partners: PartnerCard[];
    homeImages: HomeImages;
    homeYoutubeUrl: string;
}>();
const { t } = useTranslations();

const isVideoOpen = ref(false);
const isBorrelEnrollmentOpen = computed(() => props.borrelStatus === 'open' && props.borrelEvent !== null);
const isBorrelEventToday = computed(() => props.borrelStatus === 'today');
const shouldShowBorrelCount = computed(() => isBorrelEnrollmentOpen.value && props.closingBorrelCount >= 25);

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

const extractYoutubeId = (value: string | null | undefined) => {
    const fallback = 'yMBxJQk7gbg';
    const source = value?.trim();

    if (!source) return fallback;

    try {
        const url = new URL(source.startsWith('www.') ? `https://${source}` : source);
        const videoId = url.searchParams.get('v');

        if (videoId) return videoId;

        const parts = url.pathname.split('/').filter(Boolean);

        if (url.hostname.includes('youtu.be') && parts[0]) return parts[0];

        const videoSegmentIndex = parts.findIndex((part) => ['embed', 'shorts', 'live'].includes(part));

        if (videoSegmentIndex >= 0 && parts[videoSegmentIndex + 1]) {
            return parts[videoSegmentIndex + 1];
        }
    } catch {
        // The setting may be a raw YouTube video ID instead of a URL.
    }

    return source.split(/[?&]/)[0] || fallback;
};

const youtubeEmbedUrl = computed(() => {
    const id = extractYoutubeId(props.homeYoutubeUrl);
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
    event_id: props.borrelEvent?.id ?? null,
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

    <header class="brand-hero overflow-hidden px-6 pt-14 pb-16 lg:px-16">
        <div class="relative z-10 mx-auto max-w-7xl">
            <!-- Top: copy + image -->
            <div class="grid items-center gap-10 lg:grid-cols-12">
                <div class="lg:col-span-7">
                    <p class="brand-eyebrow">{{ t('home.eyebrow') }}</p>

                    <h1 class="mt-6 text-4xl font-semibold tracking-tight text-foreground sm:text-5xl lg:text-6xl">{{ t('home.title') }}</h1>

                    <p class="mt-4 max-w-xl text-base leading-relaxed text-muted-foreground sm:text-lg">
                        <template v-if="isBorrelEnrollmentOpen">{{ t('home.introOpen') }}</template>
                        <template v-else-if="isBorrelEventToday">{{ t('home.introToday') }}</template>
                        <template v-else>{{ t('home.introClosed') }}</template>
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Link
                            class="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-lg ring-1 shadow-primary/20 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                            href="/over-ons"
                        >
                            {{ t('home.learnMore') }}
                        </Link>

                        <Link
                            class="inline-flex items-center justify-center rounded-xl bg-white/80 px-6 py-3 text-sm font-semibold text-foreground shadow-sm ring-1 ring-border/80 backdrop-blur transition hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none dark:bg-white/10 dark:hover:bg-white/15"
                            href="/edities"
                        >
                            {{ t('home.previousEditions') }}
                        </Link>

                        <Link
                            class="inline-flex items-center justify-center rounded-xl bg-secondary px-6 py-3 text-sm font-semibold text-secondary-foreground shadow-lg ring-1 shadow-secondary/20 ring-secondary/25 transition hover:bg-secondary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                            href="/bedrijf-toegang"
                        >
                            {{ t('home.companyEnrollment') }}
                        </Link>

                        <Link
                            v-if="isBorrelEnrollmentOpen"
                            href="#borrel"
                            class="inline-flex items-center justify-center rounded-xl bg-secondary px-6 py-3 text-sm font-semibold text-secondary-foreground shadow-lg ring-1 shadow-secondary/20 ring-secondary/25 transition hover:bg-secondary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                        >
                            {{ t('home.registerDrinks') }}
                        </Link>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-muted-foreground">
                        <div class="inline-flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-primary"></span>
                            <template v-if="props.borrelEvent">{{ t('home.nextEdition', { date: props.borrelEvent.date ?? t('home.soon') }) }}</template>
                            <template v-else>{{ t('home.noNextEdition') }}</template>
                        </div>
                        <div class="inline-flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-secondary"></span>

                            <template v-if="shouldShowBorrelCount">{{ t('home.registrations', { count: props.closingBorrelCount }) }}</template>

                            <template v-else-if="isBorrelEnrollmentOpen">{{ t('home.registerSoon') }}</template>

                            <template v-else-if="isBorrelEventToday">{{ t('home.happyConnecting') }}</template>

                            <template v-else>{{ t('home.noDrinks') }}</template>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="relative mx-auto w-full max-w-md">
                        <!-- Decorative shapes (token based) -->
                        <div class="pointer-events-none absolute -top-5 right-8 h-8 w-52 rounded-full bg-secondary/25 blur-sm"></div>
                        <div class="pointer-events-none absolute bottom-8 -left-6 h-8 w-44 rounded-full bg-primary/25 blur-sm"></div>

                        <!-- Image container -->
                        <div class="brand-card relative overflow-hidden rounded-3xl p-3">
                            <img :src="props.homeImages.hero" alt="ATIx Bedrijvendag" class="h-[420px] w-full rounded-2xl object-cover object-center" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom: 3 scopes/features -->
            <div class="mt-20 grid gap-14 md:grid-cols-3 md:gap-16">
                <div class="flex items-start gap-6">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-primary/15 ring-1 ring-primary/20">
                        <Users class="h-9 w-9 text-primary" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-semibold tracking-tight text-foreground">{{ t('home.contactTitle') }}</h3>
                        <p class="mt-3 max-w-sm text-base leading-relaxed text-muted-foreground">{{ t('home.contactText') }}</p>
                    </div>
                </div>

                <div class="flex items-start gap-6">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-secondary/15 ring-1 ring-secondary/20">
                        <Compass class="h-9 w-9 text-secondary" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-semibold tracking-tight text-foreground">{{ t('home.exploreTitle') }}</h3>
                        <p class="mt-3 max-w-sm text-base leading-relaxed text-muted-foreground">{{ t('home.exploreText') }}</p>
                    </div>
                </div>

                <div class="flex items-start gap-6">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-chart-2/15 ring-1 ring-chart-2/20">
                        <LogIn class="h-9 w-9 text-chart-2" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-semibold tracking-tight text-foreground">{{ t('home.walkInTitle') }}</h3>
                        <p class="mt-3 max-w-sm text-base leading-relaxed text-muted-foreground">{{ t('home.walkInText') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- What is ATIx Bedrijvendag -->
    <section class="brand-section px-6 py-20 lg:px-16">
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
                            <div class="brand-card overflow-hidden rounded-3xl p-2">
                                <div class="aspect-[3/4] w-full">
                                    <img :src="props.homeImages.infoFirst" :alt="t('home.atmosphereImageAlt')" class="h-full w-full rounded-2xl object-cover" />
                                </div>
                            </div>

                            <div class="brand-card overflow-hidden rounded-3xl p-2">
                                <div class="aspect-[3/4] w-full">
                                    <img :src="props.homeImages.infoSecond" :alt="t('home.studentsImageAlt')" class="h-full w-full rounded-2xl object-cover" />
                                </div>
                            </div>
                        </div>

                        <!-- Right: taller image -->
                        <div class="sm:w-7/12">
                            <div class="brand-card overflow-hidden rounded-3xl p-2">
                                <div class="aspect-[3/4] w-full">
                                    <img :src="props.homeImages.infoThird" :alt="t('home.companiesImageAlt')" class="h-full w-full rounded-2xl object-cover object-center" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: copy + play button -->
            <div class="lg:col-span-6">
                <p class="text-sm font-semibold text-primary">{{ t('home.whatIs') }}</p>

                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">{{ t('home.meetTitle') }}</h2>

                <p class="mt-4 max-w-xl text-base leading-relaxed text-muted-foreground">{{ t('home.meetText') }}</p>

                <ul class="mt-6 space-y-2 text-sm text-muted-foreground">
                    <li class="flex gap-3">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary"></span>
                        {{ t('home.pointTechnical') }}
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary"></span>
                        {{ t('home.pointExplore') }}
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary"></span>
                        {{ t('home.pointWalkIn') }}
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
                        {{ t('home.watchVideo') }}
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA under Latest editions -->
    <section class="brand-dark-cta relative overflow-hidden px-6 py-16 lg:px-16 lg:py-20">
        <img alt="" loading="lazy" width="1660" height="337" decoding="async" class="pointer-events-none absolute right-0 bottom-0" src="/images/shape/shape-16.svg" />

        <div class="relative z-10 mx-auto max-w-7xl">
            <div class="flex flex-wrap gap-8 md:flex-nowrap md:items-center md:justify-between">
                <div class="lg:w-1/2">
                    <h2 class="mb-4 text-3xl font-semibold text-white lg:text-4xl">
                        <template v-if="isBorrelEnrollmentOpen">{{ t('home.ctaOpenTitle') }}</template>
                        <template v-else-if="isBorrelEventToday">{{ t('home.happyConnecting') }}</template>
                        <template v-else>{{ t('home.ctaNoneTitle') }}</template>
                    </h2>
                    <p class="text-white/90">
                        <template v-if="isBorrelEnrollmentOpen">{{ t('home.ctaOpenText') }}</template>
                        <template v-else-if="isBorrelEventToday">{{ t('home.ctaTodayText') }}</template>
                        <template v-else>{{ t('home.ctaNoneText') }}</template>
                    </p>
                </div>

                <div class="shrink-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            v-if="isBorrelEnrollmentOpen"
                            href="#borrel"
                            class="inline-flex items-center justify-center rounded-full bg-white px-7.5 py-3 text-sm font-semibold text-black transition hover:shadow-xl focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:outline-none"
                        >
                            {{ t('home.registerDrinks') }}
                        </Link>
                        <Link
                            href="/edities"
                            class="inline-flex items-center justify-center rounded-full bg-white/10 px-7.5 py-3 text-sm font-semibold text-white ring-1 ring-white/25 transition hover:bg-white/15 focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:outline-none"
                        >
                            {{ t('home.previousEditions') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our values -->
    <section class="brand-band px-6 py-20 lg:px-16">
        <div class="mx-auto max-w-6xl">
            <h2 class="text-center text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">{{ t('home.values') }}</h2>

            <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-3 lg:gap-8">
                <div class="brand-card brand-card-hover rounded-2xl px-8 py-12">
                    <PhArrowDown :size="50" weight="duotone" class="text-primary" />
                    <h3 class="mt-11 mb-5 text-2xl font-semibold tracking-tight text-foreground">{{ t('home.approachable') }}</h3>
                    <p class="text-base leading-relaxed text-muted-foreground">{{ t('home.approachableText') }}</p>
                </div>

                <div class="brand-card brand-card-hover rounded-2xl px-8 py-12">
                    <PhBook :size="50" weight="duotone" class="text-primary" />
                    <h3 class="mt-11 mb-5 text-2xl font-semibold tracking-tight text-foreground">{{ t('home.future') }}</h3>
                    <p class="text-base leading-relaxed text-muted-foreground">{{ t('home.futureText') }}</p>
                </div>

                <div class="brand-card brand-card-hover rounded-2xl px-8 py-12">
                    <PhWine :size="50" weight="duotone" class="text-primary" />
                    <h3 class="mt-11 mb-5 text-2xl font-semibold tracking-tight text-foreground">{{ t('home.drinks') }}</h3>
                    <p class="text-base leading-relaxed text-muted-foreground">{{ t('home.drinksText') }}</p>
                </div>

                <div class="brand-card brand-card-hover rounded-2xl px-8 py-12">
                    <PhShareNetwork :size="50" weight="duotone" class="text-primary" />
                    <h3 class="mt-11 mb-5 text-2xl font-semibold tracking-tight text-foreground">{{ t('home.networking') }}</h3>
                    <p class="text-base leading-relaxed text-muted-foreground">{{ t('home.networkingText') }}</p>
                </div>

                <div class="brand-card brand-card-hover rounded-2xl px-8 py-12">
                    <PhMicrophone :size="50" weight="duotone" class="text-primary" />
                    <h3 class="mt-11 mb-5 text-2xl font-semibold tracking-tight text-foreground">{{ t('home.inspiring') }}</h3>
                    <p class="text-base leading-relaxed text-muted-foreground">{{ t('home.inspiringText') }}</p>
                </div>

                <div class="brand-card brand-card-hover rounded-2xl px-8 py-12">
                    <PhHandshake :size="50" weight="duotone" class="text-primary" />
                    <h3 class="mt-11 mb-5 text-2xl font-semibold tracking-tight text-foreground">{{ t('home.collaboration') }}</h3>
                    <p class="text-base leading-relaxed text-muted-foreground">{{ t('home.collaborationText') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Borrel Aanmelden Form -->
    <section
        id="borrel"
        class="brand-section relative overflow-hidden px-6 py-20 lg:px-16"
        style="background-image: url('/images/shape/shape-12.svg'); background-repeat: no-repeat; background-position: left bottom; background-size: 900px auto"
    >
        <div class="relative z-10 mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold text-primary">{{ t('home.drinksEyebrow') }}</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                    <template v-if="isBorrelEnrollmentOpen">{{ t('home.drinksTitle') }}</template>
                    <template v-else-if="isBorrelEventToday">{{ t('home.happyConnecting') }}</template>
                    <template v-else>{{ t('home.noFurtherEdition') }}</template>
                </h2>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">
                    <template v-if="isBorrelEnrollmentOpen">{{ t('home.drinksOpenText') }}</template>
                    <template v-else-if="isBorrelEventToday">{{ t('home.drinksTodayText') }}</template>
                    <template v-else>{{ t('home.drinksNoneText') }}</template>
                </p>

                <div
                    v-if="isBorrelEnrollmentOpen"
                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-accent px-4 py-2 text-xs font-semibold text-accent-foreground ring-1 ring-border"
                >
                    <span class="h-2 w-2 rounded-full bg-secondary"></span>

                    <template v-if="shouldShowBorrelCount">{{ t('home.alreadyRegistered', { count: props.closingBorrelCount }) }}</template>

                    <template v-else>{{ t('home.registerNow') }}</template>
                </div>
            </div>

            <div class="mx-auto mt-12 max-w-2xl">
                <div class="brand-card relative overflow-hidden rounded-2xl p-6 sm:p-8">
                    <!-- Card decorations -->
                    <div class="pointer-events-none absolute -top-10 -right-10 h-32 w-32 rounded-full bg-primary/10"></div>
                    <div class="pointer-events-none absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-secondary/10"></div>

                    <form
                        v-if="isBorrelEnrollmentOpen"
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
                            <label class="mb-2 block text-sm font-semibold text-foreground">{{ t('common.name') }}</label>
                            <input
                                v-model="borrelForm.name"
                                type="text"
                                autocomplete="name"
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                                :placeholder="t('home.fullName')"
                            />
                            <p v-if="borrelForm.errors.name" class="mt-2 text-sm text-destructive">
                                {{ borrelForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-foreground">{{ t('common.email') }}</label>
                            <input
                                v-model="borrelForm.email"
                                type="email"
                                autocomplete="email"
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                                :placeholder="t('footer.emailPlaceholder')"
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
                                {{ borrelForm.processing ? t('home.registering') : t('home.register') }}
                            </button>

                            <p class="text-xs text-muted-foreground">{{ t('home.drinksPrivacy') }}</p>
                        </div>
                    </form>

                    <div v-else class="relative text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/15 text-primary ring-1 ring-primary/20">
                            <CheckCircle2 class="h-7 w-7" />
                        </div>
                        <h3 class="mt-5 text-2xl font-semibold tracking-tight text-foreground">
                            <template v-if="isBorrelEventToday">{{ t('home.happyConnecting') }}</template>
                            <template v-else>{{ t('home.noFurtherEdition') }}</template>
                        </h3>
                        <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-muted-foreground">
                            <template v-if="isBorrelEventToday">{{ t('home.registrationClosed') }}</template>
                            <template v-else>{{ t('home.drinksNoneText') }}</template>
                        </p>
                    </div>

                    <div
                        v-if="isBorrelEnrollmentOpen && showBorrelSuccess"
                        class="mt-6 flex items-center gap-2 rounded-xl bg-emerald-500/15 p-4 text-sm text-emerald-900 ring-1 ring-emerald-500/25"
                        role="status"
                        aria-live="polite"
                    >
                        <CheckCircle2 class="h-5 w-5 shrink-0 text-emerald-600" />
                        <span>{{ t('home.registrationSuccess') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Partners -->
    <section id="partners" class="brand-band px-6 py-16 lg:px-16">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold text-primary">{{ t('nav.partners') }}</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">{{ t('home.partnersThanks') }}</h2>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">{{ t('home.partnersText') }}</p>
            </div>

            <div class="brand-card mt-10 rounded-2xl p-6 sm:p-8">
                <div class="flex flex-wrap items-center justify-center gap-x-14 gap-y-10">
                    <div v-for="p in props.partners" :key="p.id" class="group flex basis-1/2 items-center justify-center sm:basis-1/3 lg:basis-1/4">
                        <component :is="p.url ? 'a' : 'div'" :href="p.url ?? undefined" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center">
                            <img
                                v-if="p.logo_url"
                                :src="p.logo_url"
                                :alt="p.name"
                                class="max-h-16 w-full max-w-[220px] object-contain opacity-80 grayscale transition duration-200 group-hover:opacity-100 group-hover:grayscale-0"
                                loading="lazy"
                                decoding="async"
                            />
                            <div v-else class="text-sm text-muted-foreground">{{ t('common.noLogo') }}</div>
                        </component>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest editions (3 most recent past events) -->
    <section
        class="brand-section relative overflow-hidden px-6 py-20 lg:px-16"
        style="background-image: url('/images/shape/shape-13.svg'); background-repeat: no-repeat; background-position: right top; background-size: 1500px auto"
    >
        <div class="relative mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold text-primary">{{ t('home.latest') }}</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">{{ t('home.latestTitle') }}</h2>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">{{ t('home.latestText') }}</p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                <article v-for="e in latestEditions" :key="e.id" class="brand-card brand-card-hover group overflow-hidden rounded-2xl">
                    <div class="relative aspect-[16/10] w-full bg-accent/20">
                        <img v-if="e.image_url" :src="e.image_url" :alt="e.name" class="h-full w-full object-cover" />
                        <div v-else class="flex h-full w-full items-center justify-center text-sm text-muted-foreground">{{ t('common.noImage') }}</div>

                        <div
                            class="absolute top-4 left-4 inline-flex items-center rounded-full bg-background/90 px-3 py-1 text-xs font-semibold text-foreground ring-1 ring-border"
                        >
                            {{ e.date ?? t('common.unknownDate') }}
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
                                {{ t('home.viewEdition') }}
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

            <div class="relative z-[101] flex max-h-[80vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-background shadow-xl ring-1 ring-border">
                <div class="flex shrink-0 items-center justify-between gap-4 border-b border-border px-4 py-3">
                    <div class="text-sm font-semibold text-foreground">{{ t('home.video') }}</div>
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground ring-1 ring-border transition hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                        @click="closeVideo"
                        :aria-label="t('common.close')"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto overscroll-contain bg-black">
                    <div class="relative aspect-video w-full">
                        <iframe
                            :key="videoInstanceKey"
                            class="h-full w-full"
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
