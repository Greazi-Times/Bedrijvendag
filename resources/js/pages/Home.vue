<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    PhArrowDown,
    PhArrowRight,
    PhArrowUpRight,
    PhAsterisk,
    PhBook,
    PhCalendarBlank,
    PhChatsCircle,
    PhCheck,
    PhCheckCircle,
    PhCompass,
    PhDoorOpen,
    PhHandshake,
    PhMapPin,
    PhMicrophone,
    PhPlay,
    PhShareNetwork,
    PhVideoCamera,
    PhWine,
} from '@phosphor-icons/vue';
import { computed, onBeforeUnmount, ref } from 'vue';

import SiteImage from '@/components/site/SiteImage.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import VideoDialog from '@/components/site/VideoDialog.vue';
import { useTranslations } from '@/i18n';
import { formatDate } from '@/lib/date';
import { focusFirstError } from '@/lib/forms';

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
const { t, dateLocale } = useTranslations();

const isVideoOpen = ref(false);
const isBorrelEnrollmentOpen = computed(() => props.borrelStatus === 'open' && props.borrelEvent !== null);
const isBorrelEventToday = computed(() => props.borrelStatus === 'today');
const shouldShowBorrelCount = computed(() => isBorrelEnrollmentOpen.value && props.closingBorrelCount >= 25);

const nextEditionDate = computed(() => {
    if (!props.borrelEvent) return null;
    return formatDate(props.borrelEvent.date, dateLocale.value, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) ?? t('home.soon');
});

const borrelStatusText = computed(() => {
    if (shouldShowBorrelCount.value) return t('home.registrations', { count: props.closingBorrelCount });
    if (isBorrelEnrollmentOpen.value) return t('home.registerSoon');
    if (isBorrelEventToday.value) return t('home.happyConnecting');
    return t('home.noDrinks');
});

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

const toTime = (value: string | null) => {
    if (!value) return null;
    const time = Date.parse(value);
    return Number.isFinite(time) ? time : null;
};

const latestEditions = computed(() => {
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

    return picked.map(({ __t, ...e }) => ({ ...e, isUpcoming: (__t as number) >= now }));
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

const values = computed(() => [
    { icon: PhArrowDown, title: t('home.approachable'), text: t('home.approachableText') },
    { icon: PhBook, title: t('home.future'), text: t('home.futureText') },
    { icon: PhWine, title: t('home.drinks'), text: t('home.drinksText') },
    { icon: PhShareNetwork, title: t('home.networking'), text: t('home.networkingText') },
    { icon: PhMicrophone, title: t('home.inspiring'), text: t('home.inspiringText') },
    { icon: PhHandshake, title: t('home.collaboration'), text: t('home.collaborationText') },
]);

const points = computed(() => [t('home.pointTechnical'), t('home.pointExplore'), t('home.pointWalkIn')]);

// Hero title with the ATIx name picked out in brand orange.
const titleParts = computed(() => {
    const title = t('home.title');
    const index = title.indexOf('ATIx');
    if (index < 0) return { before: title, brand: '', after: '' };
    return { before: title.slice(0, index), brand: 'ATIx', after: title.slice(index + 4) };
});

// Two-tone statement: the first clause in ink, the rest steps back.
const statement = computed(() => {
    const text = t('home.meetTitle');
    const index = text.indexOf(',');
    if (index < 0) return { lead: text, rest: '' };
    return { lead: text.slice(0, index + 1), rest: text.slice(index + 1) };
});

const programmes = computed(() => ['mechatronics', 'mechanical', 'electrical', 'ict', 'businessIt', 'industrial'].map((key) => t(`companyInterest.internshipProgram.${key}.name`)));

const editionDay = (value: string | null) => formatDate(value, dateLocale.value, { day: 'numeric' });
const editionMonth = (value: string | null) => formatDate(value, dateLocale.value, { month: 'long', year: 'numeric' });

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

const submitBorrel = () => {
    borrelForm.post('/borrel-signup', {
        preserveScroll: true,
        onError: focusFirstError,
        onSuccess: () => {
            triggerBorrelSuccess();
            borrelForm.reset('name', 'email');
        },
    });
};

onBeforeUnmount(() => {
    if (borrelSuccessTimeout) window.clearTimeout(borrelSuccessTimeout);
});
</script>

<template>
    <Head title="Home" />

    <SiteLayout>
        <!-- Hero: light stage with a soft brand glow and a layered photo collage -->
        <section class="relative isolate overflow-hidden bg-canvas">
            <div class="aurora aurora-soft" aria-hidden="true"></div>
            <div class="grid-lines grid-lines-ink" aria-hidden="true"></div>

            <div class="site-container relative grid grid-cols-1 gap-14 pt-14 pb-24 md:pt-20 lg:grid-cols-12 lg:items-center lg:gap-10 lg:pt-20 lg:pb-28">
                <div class="relative z-10 lg:col-span-6">
                    <p class="enter chip chip-brand">
                        <span class="relative flex size-2" aria-hidden="true">
                            <span
                                v-if="isBorrelEnrollmentOpen || isBorrelEventToday"
                                class="absolute inline-flex size-full rounded-full bg-brand opacity-75 motion-safe:animate-ping"
                            ></span>
                            <span class="relative inline-flex size-2 rounded-full bg-brand"></span>
                        </span>
                        {{ isBorrelEnrollmentOpen ? t('home.title') : t('home.eyebrow') }}
                    </p>

                    <h1 class="enter t-display mt-6 text-ink" style="--enter-delay: 80ms">
                        <template v-if="isBorrelEnrollmentOpen">{{ t('home.borrelHeroTitle') }}</template>
                        <template v-else
                            >{{ titleParts.before }}<span class="t-accent">{{ titleParts.brand }}</span
                            >{{ titleParts.after }}</template
                        >
                    </h1>

                    <p class="enter t-lead mt-6 max-w-lg" style="--enter-delay: 160ms">
                        <template v-if="isBorrelEnrollmentOpen">{{ t('home.introOpen') }}</template>
                        <template v-else-if="isBorrelEventToday">{{ t('home.introToday') }}</template>
                        <template v-else>{{ t('home.introClosed') }}</template>
                    </p>

                    <div class="enter mt-8 flex flex-col items-start gap-3" style="--enter-delay: 240ms">
                        <a v-if="isBorrelEnrollmentOpen" href="#borrel" class="btn btn-primary btn-lg">
                            {{ t('home.registerDrinks') }}
                            <PhArrowDown :size="18" weight="bold" aria-hidden="true" />
                        </a>
                        <Link v-else href="/over-ons" class="btn btn-primary btn-lg">
                            {{ t('home.learnMore') }}
                            <PhArrowRight :size="18" weight="bold" aria-hidden="true" />
                        </Link>
                        <p v-if="isBorrelEnrollmentOpen" class="t-small">{{ t('home.borrelAudience') }}</p>
                        <Link
                            href="/voor-bedrijven"
                            class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-ink-muted underline decoration-hairline underline-offset-4 transition-colors hover:text-ink"
                        >
                            {{ t('home.companyEnrollment') }}
                            <PhArrowUpRight :size="16" class="shrink-0" aria-hidden="true" />
                        </Link>
                    </div>
                </div>

                <!-- Collage -->
                <div class="relative mx-auto w-full max-w-md sm:max-w-lg lg:col-span-6 lg:max-w-none">
                    <div
                        class="pointer-events-none absolute inset-[14%] -z-10 rounded-full bg-[conic-gradient(from_140deg,#ff6a00,#2f6fec,#ff6a00)] opacity-25 blur-[80px]"
                        aria-hidden="true"
                    ></div>

                    <div class="enter-media relative ml-auto w-[76%] rotate-[2deg]">
                        <div class="float-slow shadow-raised overflow-hidden rounded-[var(--radius-panel)] ring-1 ring-hairline">
                            <img :src="props.homeImages.hero" :alt="t('home.atmosphereImageAlt')" class="aspect-[4/5] w-full object-cover" fetchpriority="high" decoding="async" />
                        </div>
                    </div>

                    <div class="enter absolute top-[10%] left-0 w-[40%] -rotate-[5deg]" style="--enter-delay: 280ms">
                        <div class="float-slower shadow-raised overflow-hidden rounded-[var(--radius-card)] ring-4 ring-canvas">
                            <img :src="props.homeImages.infoFirst" alt="" class="aspect-square w-full object-cover" decoding="async" />
                        </div>
                    </div>

                    <!-- Ticket -->
                    <dl
                        class="enter shadow-raised relative mt-12 w-full rounded-[var(--radius-card)] bg-canvas p-5 text-sm ring-1 ring-hairline sm:absolute sm:bottom-[12%] sm:-left-6 sm:mt-0 sm:w-[54%] sm:max-w-[16rem] lg:-left-10 dark:bg-surface"
                        style="--enter-delay: 360ms"
                    >
                        <div class="flex items-center gap-2 text-ink-muted">
                            <PhCalendarBlank :size="16" aria-hidden="true" />
                            <dt>{{ t('events.nextEdition') }}</dt>
                        </div>
                        <dd class="mt-1.5 text-[0.9375rem] leading-snug font-semibold text-ink first-letter:uppercase">{{ nextEditionDate ?? t('home.noNextEdition') }}</dd>
                        <div class="mt-4 flex items-center gap-2 border-t border-hairline pt-4 text-ink-muted">
                            <PhMapPin :size="16" aria-hidden="true" />
                            <dt>{{ t('home.locationLabel') }}</dt>
                        </div>
                        <dd class="mt-1.5 leading-snug font-medium text-ink">{{ t('home.location') }}</dd>
                        <div class="mt-4 flex items-center gap-2 border-t border-hairline pt-4 text-ink-muted">
                            <PhWine :size="16" aria-hidden="true" />
                            <dt>{{ t('home.statusLabel') }}</dt>
                        </div>
                        <dd class="mt-1.5 leading-snug font-medium text-brand-ink">{{ borrelStatusText }}</dd>
                    </dl>
                </div>
            </div>
        </section>

        <!-- Programme marquee: one tilted brand band between hero and content -->
        <div class="relative z-10 -mt-8 overflow-hidden py-5 md:-mt-12" aria-hidden="true">
            <div class="tile-sunset -mx-4 -rotate-[1.5deg] py-3.5 md:py-4">
                <div class="marquee">
                    <div v-for="copy in 2" :key="copy" class="marquee-track">
                        <span
                            v-for="name in programmes"
                            :key="`${copy}-${name}`"
                            class="flex items-center gap-12 text-xl font-semibold tracking-[-0.02em] whitespace-nowrap md:text-3xl"
                        >
                            {{ name }}
                            <PhAsterisk :size="22" weight="bold" class="shrink-0 opacity-60" />
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Borrel: the page's spotlight panel -->
        <section id="borrel" class="scroll-mt-20 py-20 md:py-28">
            <div class="site-container">
                <div class="spotlight panel relative grid grid-cols-1 gap-10 overflow-hidden p-6 sm:p-10 lg:grid-cols-12 lg:items-center lg:gap-12 lg:p-14">
                    <div class="grid-lines opacity-70" aria-hidden="true"></div>
                    <div v-reveal class="relative lg:col-span-6">
                        <p class="chip chip-on-media">{{ t('home.drinksEyebrow') }}</p>
                        <h2 class="t-h2 mt-6">
                            <template v-if="isBorrelEnrollmentOpen">{{ t('home.ctaOpenTitle') }}</template>
                            <template v-else-if="isBorrelEventToday">{{ t('home.happyConnecting') }}</template>
                            <template v-else>{{ t('home.ctaNoneTitle') }}</template>
                        </h2>
                        <p class="mt-6 max-w-lg text-lg leading-relaxed text-white/80">
                            <template v-if="isBorrelEnrollmentOpen">{{ t('home.ctaOpenText') }}</template>
                            <template v-else-if="isBorrelEventToday">{{ t('home.ctaTodayText') }}</template>
                            <template v-else>{{ t('home.ctaNoneText') }}</template>
                        </p>
                        <p v-if="isBorrelEnrollmentOpen" class="mt-6 text-sm font-medium text-white/80">{{ t('home.borrelAudience') }}</p>
                        <Link v-else href="/edities" class="btn btn-light mt-9">
                            {{ t('home.previousEditions') }}
                            <PhArrowRight :size="16" weight="bold" aria-hidden="true" />
                        </Link>
                    </div>

                    <div v-reveal="100" class="relative rounded-[var(--radius-card)] bg-canvas p-6 text-ink shadow-[0_40px_90px_-30px_rgb(5_6_9/0.7)] sm:p-8 lg:col-span-6">
                        <template v-if="isBorrelEnrollmentOpen">
                            <h3 class="t-h3">{{ t('home.drinksTitle') }}</h3>
                            <p class="t-small mt-2">{{ t('home.drinksOpenText') }}</p>
                            <p class="chip chip-brand mt-4">
                                <template v-if="shouldShowBorrelCount">{{ t('home.alreadyRegistered', { count: props.closingBorrelCount }) }}</template>
                                <template v-else>{{ t('home.registerNow') }}</template>
                            </p>

                            <form class="mt-7 grid gap-5" novalidate @submit.prevent="submitBorrel">
                                <div class="field">
                                    <label for="borrel-name" class="field-label">{{ t('common.name') }}</label>
                                    <input
                                        id="borrel-name"
                                        v-model="borrelForm.name"
                                        type="text"
                                        name="name"
                                        autocomplete="name"
                                        class="input"
                                        :placeholder="t('home.fullName')"
                                        :aria-invalid="borrelForm.errors.name ? 'true' : undefined"
                                        :aria-describedby="borrelForm.errors.name ? 'borrel-name-error' : undefined"
                                    />
                                    <p v-if="borrelForm.errors.name" id="borrel-name-error" class="field-error">{{ borrelForm.errors.name }}</p>
                                </div>

                                <div class="field">
                                    <label for="borrel-email" class="field-label">{{ t('common.email') }}</label>
                                    <input
                                        id="borrel-email"
                                        v-model="borrelForm.email"
                                        type="email"
                                        name="email"
                                        autocomplete="email"
                                        spellcheck="false"
                                        class="input"
                                        :placeholder="t('footer.emailPlaceholder')"
                                        :aria-invalid="borrelForm.errors.email ? 'true' : undefined"
                                        :aria-describedby="borrelForm.errors.email ? 'borrel-email-error' : undefined"
                                    />
                                    <p v-if="borrelForm.errors.email" id="borrel-email-error" class="field-error">{{ borrelForm.errors.email }}</p>
                                </div>

                                <div class="mt-1 grid gap-3">
                                    <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="borrelForm.processing">
                                        {{ borrelForm.processing ? t('home.registering') : t('home.register') }}
                                    </button>
                                    <p class="field-help text-center">{{ t('home.drinksPrivacy') }}</p>
                                </div>
                            </form>

                            <div aria-live="polite">
                                <p v-if="showBorrelSuccess" class="alert alert-success mt-5" role="status">
                                    <PhCheckCircle :size="20" weight="fill" class="shrink-0 text-success" aria-hidden="true" />
                                    {{ t('home.registrationSuccess') }}
                                </p>
                            </div>
                        </template>

                        <div v-else class="flex flex-col items-start py-2">
                            <span class="tile-sunset flex size-12 items-center justify-center rounded-[var(--radius-input)]">
                                <PhWine :size="24" weight="bold" aria-hidden="true" />
                            </span>
                            <h3 class="t-h3 mt-8">
                                <template v-if="isBorrelEventToday">{{ t('home.happyConnecting') }}</template>
                                <template v-else>{{ t('home.noFurtherEdition') }}</template>
                            </h3>
                            <p class="t-body mt-2 max-w-md">
                                <template v-if="isBorrelEventToday">{{ t('home.registrationClosed') }}</template>
                                <template v-else>{{ t('home.drinksNoneText') }}</template>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statement + video and information cards -->
        <section class="pt-16 pb-20 md:pt-24 md:pb-28">
            <div class="site-container">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:gap-10">
                    <h2 v-reveal class="t-h2 lg:col-span-7">
                        <span class="text-ink">{{ statement.lead }}</span
                        ><span class="t-quiet">{{ statement.rest }}</span>
                    </h2>
                    <div v-reveal="60" class="lg:col-span-5">
                        <p class="t-body">{{ t('home.meetText') }}</p>
                        <ul class="mt-5 grid gap-2.5">
                            <li v-for="point in points" :key="point" class="flex items-start gap-3 text-[0.9375rem] font-medium text-ink">
                                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-brand text-[#0b0f19]">
                                    <PhCheck :size="12" weight="bold" aria-hidden="true" />
                                </span>
                                {{ point }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-4 md:mt-14 md:grid-cols-2 lg:grid-cols-12">
                    <!-- Video: the whole photo is the control, with the play affordance always visible -->
                    <button
                        v-reveal
                        type="button"
                        class="media group relative flex min-h-[20rem] flex-col justify-between p-5 text-left text-white sm:min-h-[24rem] sm:p-6 md:col-span-2 lg:col-span-7 lg:row-span-2 lg:min-h-[28rem]"
                        @click="isVideoOpen = true"
                    >
                        <img
                            :src="props.homeImages.infoThird"
                            :alt="t('home.companiesImageAlt')"
                            class="absolute inset-0 transition-transform duration-[1200ms] ease-[var(--ease-out-expo)] group-hover:scale-[1.03]"
                            loading="lazy"
                            decoding="async"
                        />
                        <span class="absolute inset-0 bg-[rgb(5_6_9/0.28)] transition-colors duration-300 group-hover:bg-[rgb(5_6_9/0.4)]" aria-hidden="true"></span>
                        <span class="absolute inset-x-0 bottom-0 h-2/5 bg-[linear-gradient(to_top,rgb(5_6_9/0.8),transparent)]" aria-hidden="true"></span>

                        <span class="chip chip-on-media relative self-start">
                            <PhVideoCamera :size="14" weight="bold" aria-hidden="true" />
                            {{ t('home.video') }}
                        </span>

                        <span class="absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 items-center justify-center" aria-hidden="true">
                            <span
                                class="flex size-20 items-center justify-center rounded-full bg-white text-brand-ink shadow-[0_12px_40px_-8px_rgb(0_0_0/0.5)] ring-8 ring-white/25 transition-transform duration-300 ease-[var(--ease-spring)] group-hover:scale-110 sm:size-24"
                            >
                                <PhPlay :size="32" weight="fill" class="ml-1" />
                            </span>
                        </span>

                        <span class="relative flex items-end justify-between gap-4">
                            <span class="t-h3">{{ t('home.watchVideo') }}</span>
                            <span class="hidden items-center gap-1.5 text-sm font-medium text-white/85 sm:flex">
                                <PhPlay :size="14" weight="fill" aria-hidden="true" />
                                YouTube
                            </span>
                        </span>
                    </button>

                    <div v-reveal="60" class="tile-sunset card-lift flex flex-col gap-4 rounded-[var(--radius-card)] p-6 md:col-span-1 lg:col-span-5 lg:p-8">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-[var(--radius-input)] bg-white/30">
                                <PhChatsCircle :size="22" weight="fill" aria-hidden="true" />
                            </span>
                            <h3 class="t-h3">{{ t('home.contactTitle') }}</h3>
                        </div>
                        <p class="text-[0.9375rem] leading-relaxed text-[#0b0f19]/80">{{ t('home.contactText') }}</p>
                    </div>

                    <div v-reveal="120" class="tile-blue-soft card-lift flex flex-col gap-4 rounded-[var(--radius-card)] p-6 md:col-span-1 lg:col-span-5 lg:p-8">
                        <div class="flex items-center gap-3">
                            <span class="tile-electric flex size-10 shrink-0 items-center justify-center rounded-[var(--radius-input)]">
                                <PhCompass :size="22" weight="fill" aria-hidden="true" />
                            </span>
                            <h3 class="t-h3">{{ t('home.exploreTitle') }}</h3>
                        </div>
                        <p class="t-body text-[0.9375rem]">{{ t('home.exploreText') }}</p>
                    </div>

                    <!-- Walk-in: a wide photo band so the text gets room -->
                    <div v-reveal="60" class="media group relative flex min-h-[15rem] items-end p-6 text-white md:col-span-2 lg:col-span-12 lg:min-h-[17rem] lg:p-10">
                        <img
                            :src="props.homeImages.infoFirst"
                            alt=""
                            class="absolute inset-0 object-[center_35%] transition-transform duration-[1200ms] ease-[var(--ease-out-expo)] group-hover:scale-[1.03]"
                            loading="lazy"
                            decoding="async"
                        />
                        <span
                            class="absolute inset-0 bg-[linear-gradient(to_top,rgb(5_6_9/0.9)_0%,rgb(5_6_9/0.6)_55%,rgb(5_6_9/0.25)_100%)] md:bg-[linear-gradient(to_right,rgb(5_6_9/0.85)_0%,rgb(5_6_9/0.55)_45%,rgb(5_6_9/0.1)_100%)]"
                            aria-hidden="true"
                        ></span>
                        <div class="relative max-w-lg">
                            <div class="flex items-center gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-[var(--radius-input)] bg-white/15 ring-1 ring-white/20">
                                    <PhDoorOpen :size="22" weight="fill" aria-hidden="true" />
                                </span>
                                <h3 class="t-h3">{{ t('home.walkInTitle') }}</h3>
                            </div>
                            <p class="mt-3 text-[0.9375rem] leading-relaxed text-white/85">{{ t('home.walkInText') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Values: light tinted band with interactive cards -->
        <section class="border-y border-hairline bg-surface py-20 md:py-28">
            <div class="site-container grid grid-cols-1 gap-12 lg:grid-cols-12 lg:gap-14">
                <div class="lg:col-span-4">
                    <div class="lg:sticky lg:top-28">
                        <h2 v-reveal class="t-h1 text-ink">{{ t('home.values') }}</h2>
                        <div v-reveal="80" class="mt-10 hidden lg:block">
                            <div class="media shadow-raised aspect-[4/5] rotate-[-1.5deg]">
                                <img :src="props.homeImages.infoSecond" :alt="t('home.studentsImageAlt')" loading="lazy" decoding="async" />
                            </div>
                        </div>
                    </div>
                </div>
                <ul class="grid gap-4 sm:grid-cols-2 lg:col-span-8">
                    <li
                        v-for="(value, index) in values"
                        :key="value.title"
                        v-reveal="(index % 2) * 80"
                        v-spotlight
                        class="spot card-lift bg-canvas p-6 md:p-7"
                        :class="index % 2 === 1 ? 'sm:translate-y-6' : ''"
                    >
                        <span
                            class="flex size-11 items-center justify-center rounded-[var(--radius-input)]"
                            :class="index % 3 === 0 ? 'tile-sunset' : index % 3 === 1 ? 'tile-electric' : 'bg-surface-2 text-ink'"
                        >
                            <component :is="value.icon" :size="22" weight="bold" aria-hidden="true" />
                        </span>
                        <h3 class="t-h4 mt-6 text-ink">{{ value.title }}</h3>
                        <p class="t-body mt-2 text-[0.9375rem]">{{ value.text }}</p>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Latest editions: photo-led cards with a big date -->
        <section v-if="latestEditions.length" class="pb-20 md:pb-28">
            <div class="site-container">
                <div class="flex flex-col gap-8 md:flex-row md:items-end md:justify-between">
                    <h2 v-reveal class="t-h2 max-w-3xl text-ink">{{ t('home.latestTitle') }}</h2>
                    <Link v-reveal href="/edities" class="btn btn-ink shrink-0 self-start md:self-auto">
                        {{ t('events.title') }}
                        <PhArrowRight :size="16" weight="bold" aria-hidden="true" />
                    </Link>
                </div>
                <p v-reveal="60" class="t-lead mt-5 max-w-xl">{{ t('home.latestText') }}</p>

                <div class="mt-12 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-12">
                    <article
                        v-for="(e, index) in latestEditions"
                        :key="e.id"
                        v-reveal="index * 80"
                        class="theme-dark group card-lift relative isolate flex min-h-[26rem] flex-col justify-end overflow-hidden rounded-[var(--radius-card)] bg-[#0b0f19] p-6 text-white sm:p-8"
                        :class="index === 0 ? 'md:col-span-2 lg:col-span-6 lg:min-h-[30rem]' : 'lg:col-span-3 lg:min-h-[30rem]'"
                    >
                        <div
                            class="absolute inset-0 -z-10 [&>*]:size-full [&>img]:object-cover [&>img]:transition-transform [&>img]:duration-[1200ms] [&>img]:ease-[var(--ease-out-expo)] group-hover:[&>img]:scale-[1.05]"
                        >
                            <SiteImage :src="e.image_url" :alt="e.name" />
                        </div>
                        <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_top,rgb(5_6_9/0.92)_0%,rgb(5_6_9/0.4)_50%,rgb(5_6_9/0.05)_100%)]" aria-hidden="true"></div>

                        <span v-if="e.isUpcoming" class="chip chip-brand absolute top-6 left-6 bg-brand text-[#0b0f19] shadow-none sm:top-8 sm:left-8">{{
                            t('events.upcoming')
                        }}</span>

                        <time :datetime="e.date ?? undefined" class="block">
                            <span class="block leading-none font-semibold tracking-[-0.06em]" :class="index === 0 ? 'text-[4.5rem] sm:text-[5.5rem]' : 'text-[3.75rem]'">
                                {{ editionDay(e.date) ?? '?' }}
                            </span>
                            <span class="mt-2 block text-sm font-medium text-white/70 first-letter:uppercase">{{ editionMonth(e.date) ?? t('common.unknownDate') }}</span>
                        </time>
                        <h3 class="mt-5 border-t border-white/20 pt-5" :class="index === 0 ? 't-h3' : 't-h4'">
                            <Link :href="`/edities/${e.id}`" class="after:absolute after:inset-0">{{ e.name }}</Link>
                        </h3>
                        <p v-if="index === 0 && descriptionPreview(e.description)" class="mt-2 line-clamp-2 max-w-lg text-[0.9375rem] text-white/75">
                            {{ descriptionPreview(e.description) }}
                        </p>
                        <PhArrowUpRight
                            :size="22"
                            weight="bold"
                            class="absolute top-6 right-6 text-white transition-transform duration-500 group-hover:translate-x-1 group-hover:-translate-y-1 sm:top-8 sm:right-8"
                            aria-hidden="true"
                        />
                    </article>
                </div>
            </div>
        </section>

        <!-- Partners: logo wall -->
        <section v-if="props.partners.length" id="partners" class="border-t border-hairline py-20 md:py-28">
            <div class="site-container grid grid-cols-1 gap-12 lg:grid-cols-12 lg:items-center">
                <div v-reveal class="lg:col-span-5">
                    <h2 class="t-h2 text-ink">{{ t('home.partnersThanks') }}</h2>
                    <p class="t-lead mt-5 max-w-md">{{ t('home.partnersText') }}</p>
                    <Link href="/partners" class="btn btn-secondary mt-8">
                        {{ t('home.allPartners') }}
                        <PhArrowRight :size="16" weight="bold" aria-hidden="true" />
                    </Link>
                </div>

                <ul v-reveal="80" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:col-span-7">
                    <li v-for="p in props.partners" :key="p.id">
                        <component
                            :is="p.url ? 'a' : 'div'"
                            :href="p.url ?? undefined"
                            :target="p.url ? '_blank' : undefined"
                            :rel="p.url ? 'noopener noreferrer' : undefined"
                            class="group card-lift flex aspect-[3/2] items-center justify-center rounded-[var(--radius-card)] bg-canvas px-6 ring-1 ring-hairline"
                        >
                            <img
                                v-if="p.logo_url"
                                :src="p.logo_url"
                                :alt="p.name"
                                class="max-h-14 w-auto max-w-[75%] object-contain opacity-70 grayscale transition duration-300 group-hover:opacity-100 group-hover:grayscale-0"
                                loading="lazy"
                                decoding="async"
                            />
                            <span v-else class="text-center text-sm font-medium text-ink">{{ p.name }}</span>
                        </component>
                    </li>
                </ul>
            </div>
        </section>
    </SiteLayout>

    <VideoDialog v-model:open="isVideoOpen" :src="youtubeEmbedUrl" :title="`ATIx Bedrijvendag ${t('home.video')}`" />
</template>
