<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Users, Building2, Compass, Handshake, Lightbulb, Wine, ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';

import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { useTranslations } from '@/i18n';

type AboutImages = {
    hero: string;
    infoFirst: string;
    infoSecond: string;
    infoThird: string;
};

const props = defineProps<{
    aboutImages: AboutImages;
}>();

const page = usePage();
const { t } = useTranslations();
const borrelEnrollmentOpen = computed(() => Boolean(page.props.borrelEnrollmentOpen));
</script>

<template>
    <Head :title="t('nav.about')" />

    <AppHeader class="sticky top-0 z-50" />

    <!-- Hero -->
    <header class="brand-hero overflow-hidden px-6 pt-14 pb-16 lg:px-16">
        <div class="relative z-10 mx-auto max-w-7xl">
            <div class="grid items-center gap-10 lg:grid-cols-12">
                <div class="lg:col-span-7">
                    <p class="brand-eyebrow">{{ t('about.eyebrow') }}</p>

                    <h1 class="mt-6 text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">ATIx Bedrijvendag</h1>

                    <p class="mt-4 max-w-2xl text-base leading-relaxed text-muted-foreground sm:text-lg">{{ t('about.heroText') }}</p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Link
                            href="/edities"
                            class="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-lg ring-1 shadow-primary/20 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                        >
                            {{ t('about.viewEditions') }}
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Link>

                        <Link
                            v-if="borrelEnrollmentOpen"
                            href="/#borrel"
                            class="inline-flex items-center justify-center rounded-xl bg-white/80 px-6 py-3 text-sm font-semibold text-foreground shadow-sm ring-1 ring-border/80 backdrop-blur transition hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none dark:bg-white/10 dark:hover:bg-white/15"
                        >
                            {{ t('home.registerDrinks') }}
                        </Link>
                    </div>

                    <div class="mt-10 grid gap-4 sm:grid-cols-3">
                        <div class="brand-card rounded-2xl p-5">
                            <div class="text-xs font-semibold text-muted-foreground">{{ t('about.goal') }}</div>
                            <div class="mt-2 text-sm font-semibold text-foreground">{{ t('about.connect') }}</div>
                            <p class="mt-1 text-sm text-muted-foreground">{{ t('about.connectText') }}</p>
                        </div>
                        <div class="brand-card rounded-2xl p-5">
                            <div class="text-xs font-semibold text-muted-foreground">{{ t('about.focus') }}</div>
                            <div class="mt-2 text-sm font-semibold text-foreground">{{ t('about.explore') }}</div>
                            <p class="mt-1 text-sm text-muted-foreground">{{ t('about.exploreText') }}</p>
                        </div>
                        <div class="brand-card rounded-2xl p-5">
                            <div class="text-xs font-semibold text-muted-foreground">{{ t('about.atmosphere') }}</div>
                            <div class="mt-2 text-sm font-semibold text-foreground">{{ t('about.approachable') }}</div>
                            <p class="mt-1 text-sm text-muted-foreground">{{ t('about.approachableText') }}</p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="relative mx-auto w-full max-w-md">
                        <div class="pointer-events-none absolute -top-5 right-8 h-8 w-52 rounded-full bg-secondary/25 blur-sm"></div>
                        <div class="pointer-events-none absolute bottom-8 -left-6 h-8 w-44 rounded-full bg-primary/25 blur-sm"></div>

                        <div class="brand-card relative overflow-hidden rounded-3xl p-3">
                            <img :src="props.aboutImages.hero" alt="ATIx Bedrijvendag" class="h-[420px] w-full rounded-2xl object-cover object-center" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- What is it -->
    <section class="brand-section px-6 py-20 lg:px-16">
        <div class="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-12">
            <div class="lg:col-span-6">
                <div class="relative mx-auto max-w-xl">
                    <div class="pointer-events-none absolute -top-10 -left-8 h-20 w-20 rounded-full bg-secondary/25"></div>
                    <div class="pointer-events-none absolute -top-2 right-10 h-10 w-10 rounded-full bg-accent"></div>
                    <div class="pointer-events-none absolute -bottom-10 left-10 h-32 w-32 rounded-t-full bg-primary/25"></div>

                    <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                        <div class="flex flex-col gap-6 sm:w-5/12">
                            <div class="brand-card overflow-hidden rounded-3xl p-2">
                                <div class="aspect-[3/4] w-full">
                                    <img :src="props.aboutImages.infoFirst" :alt="t('about.atmosphereImageAlt')" class="h-full w-full rounded-2xl object-cover" />
                                </div>
                            </div>

                            <div class="brand-card overflow-hidden rounded-3xl p-2">
                                <div class="aspect-[3/4] w-full">
                                    <img :src="props.aboutImages.infoSecond" :alt="t('about.studentsImageAlt')" class="h-full w-full rounded-2xl object-cover" />
                                </div>
                            </div>
                        </div>

                        <div class="sm:w-7/12">
                            <div class="brand-card overflow-hidden rounded-3xl p-2">
                                <div class="aspect-[3/4] w-full">
                                    <img :src="props.aboutImages.infoThird" :alt="t('about.companiesImageAlt')" class="h-full w-full rounded-2xl object-cover object-center" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-6">
                <p class="text-sm font-semibold text-primary">{{ t('about.whatIs') }}</p>

                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">{{ t('about.title') }}</h2>

                <p class="mt-4 max-w-xl text-base leading-relaxed text-muted-foreground">{{ t('about.text') }}</p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="brand-card rounded-2xl p-5">
                        <div class="text-sm font-semibold text-foreground">{{ t('about.students') }}</div>
                        <p class="mt-2 text-sm text-muted-foreground">{{ t('about.studentsText') }}</p>
                    </div>
                    <div class="brand-card rounded-2xl p-5">
                        <div class="text-sm font-semibold text-foreground">{{ t('about.companies') }}</div>
                        <p class="mt-2 text-sm text-muted-foreground">{{ t('about.companiesText') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="brand-band px-6 py-20 lg:px-16">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold text-primary">{{ t('about.values') }}</p>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">{{ t('about.approach') }}</h2>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">{{ t('about.approachText') }}</p>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div class="brand-card brand-card-hover group rounded-2xl p-8">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary/15">
                        <Compass class="h-6 w-6 text-primary" />
                    </div>
                    <h3 class="mt-6 text-xl font-semibold tracking-tight text-foreground">{{ t('home.approachable') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-muted-foreground">{{ t('about.approachableValueText') }}</p>
                </div>

                <div class="brand-card brand-card-hover group rounded-2xl p-8">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-secondary/15">
                        <Lightbulb class="h-6 w-6 text-secondary" />
                    </div>
                    <h3 class="mt-6 text-xl font-semibold tracking-tight text-foreground">{{ t('home.future') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-muted-foreground">{{ t('about.futureValueText') }}</p>
                </div>

                <div class="brand-card brand-card-hover group rounded-2xl p-8">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-chart-2/15">
                        <Wine class="h-6 w-6 text-chart-2" />
                    </div>
                    <h3 class="mt-6 text-xl font-semibold tracking-tight text-foreground">{{ t('home.drinks') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-muted-foreground">{{ t('about.drinksValueText') }}</p>
                </div>

                <div class="brand-card brand-card-hover group rounded-2xl p-8">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-primary/15">
                        <Users class="h-6 w-6 text-primary" />
                    </div>
                    <h3 class="mt-6 text-xl font-semibold tracking-tight text-foreground">{{ t('home.networking') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-muted-foreground">{{ t('about.networkingValueText') }}</p>
                </div>

                <div class="brand-card brand-card-hover group rounded-2xl p-8">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-secondary/15">
                        <Building2 class="h-6 w-6 text-secondary" />
                    </div>
                    <h3 class="mt-6 text-xl font-semibold tracking-tight text-foreground">{{ t('home.inspiring') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-muted-foreground">{{ t('about.inspiringValueText') }}</p>
                </div>

                <div class="brand-card brand-card-hover group rounded-2xl p-8">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-chart-2/15">
                        <Handshake class="h-6 w-6 text-chart-2" />
                    </div>
                    <h3 class="mt-6 text-xl font-semibold tracking-tight text-foreground">{{ t('home.collaboration') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-muted-foreground">{{ t('about.collaborationValueText') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="brand-dark-cta relative overflow-hidden px-6 py-16 lg:px-16 lg:py-20">
        <img alt="" loading="lazy" width="1660" height="337" decoding="async" class="pointer-events-none absolute right-0 bottom-0" src="/images/shape/shape-16.svg" />

        <div class="relative z-10 mx-auto max-w-7xl">
            <div class="flex flex-wrap gap-8 md:flex-nowrap md:items-center md:justify-between">
                <div class="lg:w-1/2">
                    <h2 class="mb-4 text-3xl font-semibold text-white lg:text-4xl">{{ t('about.ctaTitle') }}</h2>
                    <p class="text-white/90">
                        <template v-if="borrelEnrollmentOpen">{{ t('about.ctaOpen') }}</template>
                        <template v-else>{{ t('about.ctaClosed') }}</template>
                    </p>
                </div>

                <div class="shrink-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            href="/edities"
                            class="inline-flex items-center justify-center rounded-full bg-white px-7.5 py-3 text-sm font-semibold text-black transition hover:shadow-xl focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:outline-none"
                        >
                            {{ t('about.viewAllEditions') }}
                        </Link>
                        <Link
                            v-if="borrelEnrollmentOpen"
                            href="/#borrel"
                            class="inline-flex items-center justify-center rounded-full bg-white/10 px-7.5 py-3 text-sm font-semibold text-white ring-1 ring-white/25 transition hover:bg-white/15 focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:outline-none"
                        >
                            {{ t('home.registerDrinks') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <AppFooter />
</template>
