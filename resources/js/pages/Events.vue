<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { PhArrowRight, PhArrowUpRight, PhCalendarBlank, PhImages } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';

import EmptyState from '@/components/site/EmptyState.vue';
import PageIntro from '@/components/site/PageIntro.vue';
import SiteDialog from '@/components/site/SiteDialog.vue';
import SiteImage from '@/components/site/SiteImage.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import { useTranslations } from '@/i18n';
import { sanitizeHtml } from '@/lib/sanitize';

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
const { dateLocale, t } = useTranslations();

const selected = ref<EventItem | null>(null);
const isModalOpen = ref(false);

const featuredUpcoming = computed(() => props.upcoming[0] ?? null);
const otherUpcoming = computed(() => props.upcoming.slice(1));

function openModal(event: EventItem) {
    selected.value = event;
    isModalOpen.value = true;
}

function formatDateRange(startsAt: string | null, endsAt: string | null) {
    if (!startsAt && !endsAt) return t('common.unknownDate');

    const fmt = new Intl.DateTimeFormat(dateLocale.value, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

    const start = startsAt ? fmt.format(new Date(startsAt)) : null;
    const end = endsAt ? fmt.format(new Date(endsAt)) : null;

    if (start && end && start !== end) return `${start} - ${end}`;
    return start ?? end ?? t('common.unknownDate');
}

function dayOf(value: string | null) {
    if (!value) return '?';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '?' : new Intl.DateTimeFormat(dateLocale.value, { day: 'numeric' }).format(date);
}

function monthMeta(event: EventItem) {
    const date = event.starts_at ? new Date(event.starts_at) : null;
    const month = date && !Number.isNaN(date.getTime()) ? new Intl.DateTimeFormat(dateLocale.value, { month: 'long', year: 'numeric' }).format(date) : t('common.unknownDate');
    return event.location ? `${month} · ${event.location}` : month;
}

function eventMeta(event: EventItem) {
    const date = formatDateRange(event.starts_at, event.ends_at);
    return event.location ? `${date} · ${event.location}` : date;
}
</script>

<template>
    <Head :title="t('events.title')" />

    <SiteLayout>
        <PageIntro :eyebrow="t('events.eyebrow')" :title="t('events.title')" :lead="t('events.intro')" />

        <!-- Upcoming -->
        <section class="site-container pt-14 pb-20 md:pt-20 md:pb-28" aria-labelledby="upcoming-heading">
            <div class="flex items-baseline justify-between gap-4 border-b border-hairline pb-5">
                <h2 id="upcoming-heading" class="t-h3 text-ink">{{ t('events.upcoming') }}</h2>
                <span class="text-sm text-ink-muted">{{ t('events.eventCount', { count: props.upcoming.length }) }}</span>
            </div>

            <template v-if="featuredUpcoming">
                <article v-reveal class="theme-dark group relative isolate mt-8 grid grid-cols-1 overflow-hidden rounded-[var(--radius-panel)] lg:grid-cols-12">
                    <div class="aurora opacity-80" aria-hidden="true"></div>
                    <div class="media media-zoom relative aspect-[16/10] rounded-none lg:col-span-7 lg:aspect-auto lg:min-h-[30rem]">
                        <SiteImage :src="featuredUpcoming.header_image_url" :alt="featuredUpcoming.title" loading="eager" />
                    </div>
                    <div class="relative flex flex-col p-7 sm:p-10 lg:col-span-5 lg:p-12">
                        <p class="chip chip-brand self-start bg-brand text-[#0b0f19] shadow-none">{{ t('events.nextEdition') }}</p>
                        <p class="mt-auto pt-10 text-[5.5rem] leading-none font-semibold tracking-[-0.06em] text-ink sm:text-[7rem]">{{ dayOf(featuredUpcoming.starts_at) }}</p>
                        <p class="mt-2 text-sm text-ink-muted first-letter:uppercase">{{ monthMeta(featuredUpcoming) }}</p>
                        <h3 class="t-h2 mt-6 text-ink">
                            <button type="button" class="text-left after:absolute after:inset-0 after:rounded-[var(--radius-panel)]" @click="openModal(featuredUpcoming)">
                                {{ featuredUpcoming.title }}
                            </button>
                        </h3>
                        <p v-if="featuredUpcoming.short_description" class="t-body mt-4 line-clamp-3">{{ featuredUpcoming.short_description }}</p>
                        <p class="mt-8 inline-flex items-center gap-1.5 text-sm font-medium text-brand">
                            {{ t('events.clickDetails') }}
                            <PhArrowRight :size="14" weight="bold" class="transition-transform duration-300 group-hover:translate-x-1" aria-hidden="true" />
                        </p>
                    </div>
                </article>

                <ul v-if="otherUpcoming.length" class="mt-14 grid gap-x-5 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                    <li v-for="(event, index) in otherUpcoming" :key="event.id" v-reveal="(index % 3) * 60" class="group relative">
                        <div class="media media-zoom aspect-[4/3]">
                            <SiteImage :src="event.header_image_url" :alt="event.title" />
                        </div>
                        <p class="t-mono mt-4 text-sm text-ink-muted">{{ eventMeta(event) }}</p>
                        <h3 class="t-h4 mt-2 line-clamp-2 text-ink">
                            <button type="button" class="text-left after:absolute after:inset-0 after:rounded-[var(--radius-card)]" @click="openModal(event)">
                                {{ event.title }}
                            </button>
                        </h3>
                        <p v-if="event.short_description" class="t-small mt-2 line-clamp-2">{{ event.short_description }}</p>
                    </li>
                </ul>
            </template>

            <div v-else class="mt-8">
                <EmptyState :icon="PhCalendarBlank" :title="t('events.noneUpcoming')" />
            </div>
        </section>

        <!-- Past -->
        <section class="border-t border-hairline bg-surface/60 py-20 md:py-28" aria-labelledby="past-heading">
            <div class="site-container">
                <div class="flex items-baseline justify-between gap-4 border-b border-hairline pb-5">
                    <h2 id="past-heading" class="t-h3 text-ink">{{ t('events.previous') }}</h2>
                    <span class="text-sm text-ink-muted">{{ t('events.editionCount', { count: props.past.length }) }}</span>
                </div>

                <ul v-if="props.past.length" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <li
                        v-for="(event, index) in props.past"
                        :key="event.id"
                        v-reveal="(index % 3) * 60"
                        class="theme-dark group card-lift relative isolate flex min-h-[24rem] flex-col justify-end overflow-hidden rounded-[var(--radius-card)] p-6 text-white sm:p-7"
                    >
                        <div
                            class="absolute inset-0 -z-10 [&>*]:size-full [&>img]:object-cover [&>img]:transition-transform [&>img]:duration-[1200ms] [&>img]:ease-[var(--ease-out-expo)] group-hover:[&>img]:scale-[1.05]"
                        >
                            <SiteImage :src="event.header_image_url" :alt="event.title" />
                        </div>
                        <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_top,rgb(5_6_9/0.92)_0%,rgb(5_6_9/0.35)_55%,rgb(5_6_9/0.05)_100%)]" aria-hidden="true"></div>
                        <PhArrowUpRight
                            :size="20"
                            weight="bold"
                            class="absolute top-6 right-6 transition-transform duration-500 group-hover:translate-x-1 group-hover:-translate-y-1"
                            aria-hidden="true"
                        />
                        <p class="text-[4.5rem] leading-none font-semibold tracking-[-0.06em]">{{ dayOf(event.starts_at) }}</p>
                        <p class="mt-2 text-sm text-white/70 first-letter:uppercase">{{ monthMeta(event) }}</p>
                        <h3 class="t-h4 mt-4 border-t border-white/20 pt-4">
                            <button type="button" class="text-left after:absolute after:inset-0" @click="openModal(event)">{{ event.title }}</button>
                        </h3>
                        <p class="mt-1 line-clamp-2 text-sm text-white/70">{{ event.short_description || t('events.clickMore') }}</p>
                    </li>
                </ul>

                <div v-else class="mt-8">
                    <EmptyState :icon="PhImages" :title="t('events.nonePrevious')" />
                </div>
            </div>
        </section>
    </SiteLayout>

    <SiteDialog v-if="selected" v-model:open="isModalOpen" :title="selected.title" :meta="eventMeta(selected)" size="lg">
        <div v-if="selected.header_image_url" class="media aspect-[16/9]">
            <SiteImage :src="selected.header_image_url" :alt="selected.title" loading="eager" />
        </div>
        <div :class="selected.header_image_url ? 'mt-6' : ''">
            <div v-if="selected.description_html" class="rich" v-html="sanitizeHtml(selected.description_html)" />
            <p v-else class="t-body">{{ t('common.noDescription') }}</p>
        </div>

        <template #footer>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="t-small">{{ t('events.modalHint') }}</p>
                <div class="flex flex-wrap gap-2">
                    <a v-if="selected.gallery_url" :href="selected.gallery_url" target="_blank" rel="noreferrer" class="btn btn-secondary">
                        {{ t('events.viewPhotos') }}
                        <PhArrowUpRight :size="16" weight="bold" aria-hidden="true" />
                    </a>
                    <a v-if="selected.edition_url" :href="selected.edition_url" class="btn btn-primary">
                        {{ t('events.viewFull') }}
                        <PhArrowRight :size="16" weight="bold" aria-hidden="true" />
                    </a>
                </div>
            </div>
        </template>
    </SiteDialog>
</template>
