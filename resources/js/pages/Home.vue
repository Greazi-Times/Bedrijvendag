<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    PhArrowDown,
    PhArrowRight,
    PhArrowUpRight,
    PhCalendarBlank,
    PhChatsCircle,
    PhCheck,
    PhCheckCircle,
    PhCompass,
    PhDoorOpen,
    PhHandshake,
    PhHandWaving,
    PhMapPin,
    PhMicrophone,
    PhPlay,
    PhShareNetwork,
    PhTrendUp,
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
    { icon: PhHandWaving, title: t('home.approachable'), text: t('home.approachableText') },
    { icon: PhTrendUp, title: t('home.future'), text: t('home.futureText') },
    { icon: PhWine, title: t('home.drinks'), text: t('home.drinksText') },
    { icon: PhShareNetwork, title: t('home.networking'), text: t('home.networkingText') },
    { icon: PhMicrophone, title: t('home.inspiring'), text: t('home.inspiringText') },
    { icon: PhHandshake, title: t('home.collaboration'), text: t('home.collaborationText') },
]);

const ways = computed(() => [
    { icon: PhChatsCircle, title: t('home.contactTitle'), text: t('home.contactText') },
    { icon: PhCompass, title: t('home.exploreTitle'), text: t('home.exploreText') },
    { icon: PhDoorOpen, title: t('home.walkInTitle'), text: t('home.walkInText') },
]);

const points = computed(() => [t('home.pointTechnical'), t('home.pointExplore'), t('home.pointWalkIn')]);

// Hero title with the ATIx name picked out in brand orange.
const titleParts = computed(() => {
    const title = t('home.title');
    const index = title.indexOf('ATIx');
    if (index < 0) return { before: title, brand: '', after: '' };
    return { before: title.slice(0, index), brand: 'ATIx', after: title.slice(index + 4) };
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
        <!-- Hero: what, when and where beside one real photo of the day -->
        <section class="hero-ambient">
            <div class="site-container grid grid-cols-1 gap-10 pt-8 pb-10 md:pt-12 lg:grid-cols-12 lg:items-center lg:gap-14 lg:pt-14 lg:pb-14">
                <div class="lg:col-span-5">
                    <p class="enter t-eyebrow">{{ isBorrelEnrollmentOpen ? t('home.title') : t('home.eyebrow') }}</p>

                    <h1 class="enter t-display mt-4 text-ink" style="--enter-delay: 60ms">
                        <template v-if="isBorrelEnrollmentOpen">{{ t('home.borrelHeroTitle') }}</template>
                        <template v-else
                            >{{ titleParts.before }}<span class="t-accent">{{ titleParts.brand }}</span
                            >{{ titleParts.after }}</template
                        >
                    </h1>

                    <p class="enter t-lead mt-5 max-w-md" style="--enter-delay: 120ms">
                        <template v-if="isBorrelEnrollmentOpen">{{ t('home.introOpen') }}</template>
                        <template v-else-if="isBorrelEventToday">{{ t('home.introToday') }}</template>
                        <template v-else>{{ t('home.introClosed') }}</template>
                    </p>

                    <div class="enter mt-8 flex flex-wrap items-center gap-x-6 gap-y-4" style="--enter-delay: 180ms">
                        <a v-if="isBorrelEnrollmentOpen" href="#borrel" class="btn btn-primary btn-lg">
                            {{ t('home.registerDrinks') }}
                            <PhArrowDown :size="18" weight="bold" aria-hidden="true" />
                        </a>
                        <Link v-else href="/over-ons" class="btn btn-primary btn-lg">
                            {{ t('home.learnMore') }}
                            <PhArrowRight :size="18" weight="bold" aria-hidden="true" />
                        </Link>
                        <Link href="/voor-bedrijven" class="t-link inline-flex items-center gap-1.5 text-[0.9375rem] font-medium">
                            {{ t('nav.forCompanies') }}
                            <PhArrowUpRight :size="16" class="shrink-0" aria-hidden="true" />
                        </Link>
                    </div>

                    <dl class="enter mt-10 grid grid-cols-1 gap-5 border-t border-hairline pt-6 sm:grid-cols-2" style="--enter-delay: 240ms">
                        <div>
                            <dt class="t-small flex items-center gap-2">
                                <PhCalendarBlank :size="16" aria-hidden="true" />
                                {{ t('events.nextEdition') }}
                            </dt>
                            <dd class="mt-1 font-semibold text-ink first-letter:uppercase">{{ nextEditionDate ?? t('home.noNextEdition') }}</dd>
                        </div>
                        <div>
                            <dt class="t-small flex items-center gap-2">
                                <PhMapPin :size="16" aria-hidden="true" />
                                {{ t('home.locationLabel') }}
                            </dt>
                            <dd class="mt-1 font-semibold text-ink">{{ t('home.location') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="enter-media lg:col-span-7">
                    <div class="media media-depth aspect-[4/3] rounded-[var(--radius-panel)] lg:aspect-[5/4]">
                        <img :src="props.homeImages.hero" :alt="t('home.atmosphereImageAlt')" fetchpriority="high" decoding="async" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Who it is for: the programmes as one centred line under the hero -->
        <section class="bg-canvas pb-6 md:pb-8" aria-labelledby="programmes-label">
            <div class="site-container">
                <div
                    class="flex flex-col items-center gap-2.5 rounded-[var(--radius-card)] bg-surface px-5 py-5 text-center ring-1 ring-hairline lg:flex-row lg:justify-center lg:gap-5 lg:px-8 lg:text-left"
                >
                    <p id="programmes-label" class="shrink-0 text-sm font-semibold text-ink">{{ t('home.programmesLabel') }}</p>
                    <span class="hidden h-4 w-px shrink-0 bg-hairline lg:block" aria-hidden="true"></span>
                    <ul class="flex flex-wrap justify-center gap-x-4 gap-y-1 text-[0.9375rem] text-ink-muted lg:gap-x-2.5">
                        <li v-for="(name, index) in programmes" :key="name" class="whitespace-nowrap">
                            {{ name }}<span v-if="index < programmes.length - 1" class="ml-2.5 hidden text-ink-subtle lg:inline" aria-hidden="true">·</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- What the day is: statement, video and the three ways to take part -->
        <section class="site-section">
            <div class="site-container">
                <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:items-center lg:gap-14">
                    <div v-reveal class="lg:col-span-5">
                        <h2 class="t-h2 text-ink">{{ t('home.meetTitle') }}</h2>
                        <p class="t-body mt-5">{{ t('home.meetText') }}</p>
                        <ul class="mt-6 grid gap-3">
                            <li v-for="point in points" :key="point" class="flex items-start gap-3 text-[0.9375rem] font-medium text-ink">
                                <PhCheck :size="18" weight="bold" class="mt-0.5 shrink-0 text-brand-ink" aria-hidden="true" />
                                {{ point }}
                            </li>
                        </ul>
                    </div>

                    <!-- Video: the whole preview is the control; a clear play button and label say so -->
                    <button
                        v-reveal="60"
                        type="button"
                        class="media media-depth group relative block aspect-video w-full rounded-[var(--radius-panel)] text-left text-white lg:col-span-7"
                        :aria-label="`${t('home.watchVideo')} (YouTube)`"
                        @click="isVideoOpen = true"
                    >
                        <img :src="props.homeImages.infoThird" :alt="t('home.companiesImageAlt')" class="group-hover:scale-[1.02]" loading="lazy" decoding="async" />
                        <span class="absolute inset-0 bg-[rgb(5_6_9/0.18)] transition-colors duration-300 group-hover:bg-[rgb(5_6_9/0.3)]" aria-hidden="true"></span>
                        <span class="absolute inset-x-0 bottom-0 h-1/3 bg-[linear-gradient(to_top,rgb(5_6_9/0.65),transparent)]" aria-hidden="true"></span>

                        <span
                            class="absolute top-1/2 left-1/2 flex size-16 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-white text-ink shadow-[0_8px_24px_-6px_rgb(0_0_0/0.45)] transition-transform duration-300 group-hover:scale-105 sm:size-20"
                            aria-hidden="true"
                        >
                            <PhPlay :size="28" weight="fill" class="ml-1" />
                        </span>

                        <span class="absolute inset-x-5 bottom-5 flex items-center justify-between gap-4 sm:inset-x-6 sm:bottom-6" aria-hidden="true">
                            <span class="text-base font-semibold sm:text-lg">{{ t('home.watchVideo') }}</span>
                            <span class="chip chip-on-media">
                                <PhVideoCamera :size="14" weight="bold" />
                                {{ t('home.video') }}
                            </span>
                        </span>
                    </button>
                </div>

                <ul class="mt-16 grid grid-cols-1 gap-10 border-t border-hairline pt-10 md:grid-cols-3 md:gap-8 lg:mt-20">
                    <li v-for="(item, index) in ways" :key="item.title" v-reveal="index * 60">
                        <component :is="item.icon" :size="24" class="text-brand-ink" aria-hidden="true" />
                        <h3 class="t-h4 mt-4 text-ink">{{ item.title }}</h3>
                        <p class="t-body mt-2 text-[0.9375rem]">{{ item.text }}</p>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Borrel: sign-up beside its explanation -->
        <section id="borrel" class="site-section surface-featured scroll-mt-20 border-y border-hairline">
            <div class="site-container grid grid-cols-1 gap-10 lg:grid-cols-12 lg:items-center lg:gap-14">
                <div v-reveal class="lg:col-span-5">
                    <p class="t-eyebrow">{{ t('home.drinksEyebrow') }}</p>
                    <h2 class="t-h2 mt-4 text-ink">
                        <template v-if="isBorrelEnrollmentOpen">{{ t('home.ctaOpenTitle') }}</template>
                        <template v-else-if="isBorrelEventToday">{{ t('home.happyConnecting') }}</template>
                        <template v-else>{{ t('home.ctaNoneTitle') }}</template>
                    </h2>
                    <p class="t-lead mt-5 max-w-lg">
                        <template v-if="isBorrelEnrollmentOpen">{{ t('home.ctaOpenText') }}</template>
                        <template v-else-if="isBorrelEventToday">{{ t('home.ctaTodayText') }}</template>
                        <template v-else>{{ t('home.ctaNoneText') }}</template>
                    </p>
                    <p v-if="isBorrelEnrollmentOpen" class="t-small mt-5 font-medium">{{ t('home.borrelAudience') }}</p>
                    <Link v-else href="/edities" class="btn btn-secondary mt-8">
                        {{ t('home.previousEditions') }}
                        <PhArrowRight :size="16" weight="bold" aria-hidden="true" />
                    </Link>
                </div>

                <div v-reveal="80" class="shadow-raised rounded-[var(--radius-card)] bg-canvas p-6 text-ink ring-1 ring-hairline sm:p-8 lg:col-span-6 lg:col-start-7">
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
                        <span class="flex size-11 items-center justify-center rounded-[var(--radius-input)] bg-surface text-brand-ink">
                            <PhWine :size="22" weight="bold" aria-hidden="true" />
                        </span>
                        <h3 class="t-h3 mt-6">
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
        </section>

        <!-- Values: one photo beside a plain two-column list -->
        <section class="site-section">
            <div class="site-container">
                <h2 v-reveal class="t-h2 text-ink">{{ t('home.values') }}</h2>
                <div class="mt-10 grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-14">
                    <div v-reveal class="media hidden aspect-square rounded-[var(--radius-panel)] lg:col-span-5 lg:block">
                        <img :src="props.homeImages.infoSecond" :alt="t('home.studentsImageAlt')" loading="lazy" decoding="async" />
                    </div>
                    <ul class="grid grid-cols-1 gap-x-10 sm:grid-cols-2 lg:col-span-7">
                        <li v-for="(value, index) in values" :key="value.title" v-reveal="(index % 2) * 60" class="border-t border-hairline py-6">
                            <component :is="value.icon" :size="22" class="text-brand-ink" aria-hidden="true" />
                            <h3 class="t-h4 mt-3 text-ink">{{ value.title }}</h3>
                            <p class="t-body mt-1.5 text-[0.9375rem]">{{ value.text }}</p>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Latest editions: photo-led cards with a big date -->
        <section v-if="latestEditions.length" class="site-section bg-cool-fade border-t border-hairline">
            <div class="site-container">
                <div class="flex flex-col gap-8 md:flex-row md:items-end md:justify-between">
                    <h2 v-reveal class="t-h2 max-w-3xl text-ink">{{ t('home.latestTitle') }}</h2>
                    <Link v-reveal href="/edities" class="btn btn-secondary shrink-0 self-start md:self-auto">
                        {{ t('events.title') }}
                        <PhArrowRight :size="16" weight="bold" aria-hidden="true" />
                    </Link>
                </div>
                <p v-reveal="60" class="t-body mt-4 max-w-xl">{{ t('home.latestText') }}</p>

                <div class="mt-10 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-12">
                    <article
                        v-for="(e, index) in latestEditions"
                        :key="e.id"
                        v-reveal="index * 80"
                        class="theme-dark group relative isolate flex min-h-[22rem] flex-col justify-end overflow-hidden rounded-[var(--radius-card)] bg-[#0b0f19] p-6 text-white sm:p-8"
                        :class="index === 0 ? 'md:col-span-2 lg:col-span-6 lg:min-h-[26rem]' : 'lg:col-span-3 lg:min-h-[26rem]'"
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
                            <span class="block leading-none font-semibold tracking-[-0.04em]" :class="index === 0 ? 'text-[3.5rem]' : 'text-[2.75rem]'">
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
        <section v-if="props.partners.length" id="partners" class="site-section border-t border-hairline">
            <div class="site-container grid grid-cols-1 gap-12 lg:grid-cols-12 lg:items-center">
                <div v-reveal class="lg:col-span-5">
                    <h2 class="t-h2 text-ink">{{ t('home.partnersThanks') }}</h2>
                    <p class="t-body mt-4 max-w-md">{{ t('home.partnersText') }}</p>
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
                            class="group card-lift flex aspect-[3/2] items-center justify-center rounded-[var(--radius-card)] bg-canvas px-6 ring-1 ring-hairline transition-shadow hover:ring-brand-blue/35"
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
