<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { PhArrowRight, PhBuildings, PhCompass, PhHandshake, PhLightbulb, PhMicrophoneStage, PhStudent, PhUsersThree, PhWine } from '@phosphor-icons/vue';
import { computed } from 'vue';

import SiteLayout from '@/components/site/SiteLayout.vue';
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

const facts = computed(() => [
    { label: t('about.goal'), title: t('about.connect'), text: t('about.connectText') },
    { label: t('about.focus'), title: t('about.explore'), text: t('about.exploreText') },
    { label: t('about.atmosphere'), title: t('about.approachable'), text: t('about.approachableText') },
]);

const values = computed(() => [
    { icon: PhCompass, title: t('home.approachable'), text: t('about.approachableValueText') },
    { icon: PhLightbulb, title: t('home.future'), text: t('about.futureValueText') },
    { icon: PhWine, title: t('home.drinks'), text: t('about.drinksValueText') },
    { icon: PhUsersThree, title: t('home.networking'), text: t('about.networkingValueText') },
    { icon: PhMicrophoneStage, title: t('home.inspiring'), text: t('about.inspiringValueText') },
    { icon: PhHandshake, title: t('home.collaboration'), text: t('about.collaborationValueText') },
]);
</script>

<template>
    <Head :title="t('nav.about')" />

    <SiteLayout>
        <!-- Hero -->
        <section class="bg-intro border-b border-hairline">
            <div class="site-container pt-12 md:pt-16">
                <p class="enter t-eyebrow">{{ t('about.eyebrow') }}</p>
                <h1 class="enter t-display mt-4 text-ink" style="--enter-delay: 60ms">
                    ATIx<br />
                    <span class="t-accent">Bedrijvendag</span>
                </h1>
                <div class="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-end">
                    <p class="enter t-lead lg:col-span-6" style="--enter-delay: 120ms">{{ t('about.heroText') }}</p>
                    <div class="enter flex flex-wrap gap-3 lg:col-span-6 lg:justify-end" style="--enter-delay: 180ms">
                        <Link href="/edities" class="btn btn-primary btn-lg">
                            {{ t('about.viewEditions') }}
                            <PhArrowRight :size="18" weight="bold" aria-hidden="true" />
                        </Link>
                        <Link v-if="borrelEnrollmentOpen" href="/#borrel" class="btn btn-secondary btn-lg">{{ t('home.registerDrinks') }}</Link>
                    </div>
                </div>

                <div class="enter-media relative mt-14 md:mt-20">
                    <div class="overflow-hidden rounded-t-[var(--radius-panel)]">
                        <img :src="props.aboutImages.hero" alt="ATIx Bedrijvendag" class="aspect-[16/10] w-full object-cover md:aspect-[21/9]" fetchpriority="high" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Facts overlap the photo edge -->
        <section class="site-container relative z-10 -mt-12 md:-mt-24">
            <ul class="grid gap-3 md:grid-cols-3">
                <li
                    v-for="(fact, index) in facts"
                    :key="fact.label"
                    v-reveal="index * 80"
                    class="shadow-raised rounded-[var(--radius-card)] bg-canvas p-6 text-ink ring-1 ring-hairline lg:p-7 dark:bg-surface"
                >
                    <p class="t-eyebrow">{{ fact.label }}</p>
                    <p class="t-h3 mt-6">{{ fact.title }}</p>
                    <p class="t-body mt-2 text-[0.9375rem]">{{ fact.text }}</p>
                </li>
            </ul>
        </section>

        <!-- What is it -->
        <section class="py-20 md:py-28">
            <div class="site-container">
                <p v-reveal class="t-eyebrow">{{ t('about.whatIs') }}</p>
                <h2 v-reveal="40" class="t-h2 mt-4 max-w-4xl text-ink">
                    {{ t('about.title') }}
                </h2>

                <div class="mt-14 grid grid-cols-1 gap-4 lg:grid-cols-12">
                    <div class="grid grid-cols-2 gap-3 lg:col-span-7">
                        <div v-reveal class="media col-span-2 aspect-[16/9]">
                            <img :src="props.aboutImages.infoThird" :alt="t('about.companiesImageAlt')" loading="lazy" decoding="async" />
                        </div>
                        <div v-reveal="60" class="media aspect-square">
                            <img :src="props.aboutImages.infoFirst" :alt="t('about.atmosphereImageAlt')" loading="lazy" decoding="async" />
                        </div>
                        <div v-reveal="120" class="media aspect-square">
                            <img :src="props.aboutImages.infoSecond" :alt="t('about.studentsImageAlt')" loading="lazy" decoding="async" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 lg:col-span-5">
                        <p v-reveal class="t-lead lg:pt-2">{{ t('about.text') }}</p>
                        <div v-reveal="60" class="flex flex-1 flex-col rounded-[var(--radius-card)] bg-surface p-7 ring-1 ring-hairline lg:p-8">
                            <PhStudent :size="28" class="text-brand-ink" aria-hidden="true" />
                            <h3 class="t-h3 mt-auto pt-10">{{ t('about.students') }}</h3>
                            <p class="t-body mt-2 text-[0.9375rem]">{{ t('about.studentsText') }}</p>
                        </div>
                        <div v-reveal="120" class="flex flex-1 flex-col rounded-[var(--radius-card)] bg-surface p-7 ring-1 ring-hairline lg:p-8">
                            <PhBuildings :size="28" class="text-brand-ink" aria-hidden="true" />
                            <h3 class="t-h3 mt-auto pt-10">{{ t('about.companies') }}</h3>
                            <p class="t-body mt-2 text-[0.9375rem]">{{ t('about.companiesText') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Values: typographic rows -->
        <section class="border-y border-hairline bg-surface py-20 md:py-28">
            <div class="site-container">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-7">
                        <p v-reveal class="t-eyebrow">{{ t('about.values') }}</p>
                        <h2 v-reveal="40" class="t-h2 mt-5 text-ink">{{ t('about.approach') }}</h2>
                    </div>
                    <p v-reveal="80" class="t-lead lg:col-span-4 lg:col-start-9">{{ t('about.approachText') }}</p>
                </div>

                <ul class="mt-14 border-t border-hairline">
                    <li
                        v-for="(value, index) in values"
                        :key="value.title"
                        v-reveal="index * 40"
                        class="grid grid-cols-1 items-center gap-x-8 gap-y-2 border-b border-hairline py-6 md:grid-cols-12 md:py-7"
                    >
                        <span class="flex size-11 items-center justify-center rounded-[var(--radius-input)] bg-canvas text-brand-ink ring-1 ring-hairline md:col-span-1">
                            <component :is="value.icon" :size="22" weight="bold" aria-hidden="true" />
                        </span>
                        <h3 class="t-h3 text-ink md:col-span-5">{{ value.title }}</h3>
                        <p class="t-body md:col-span-6">{{ value.text }}</p>
                    </li>
                </ul>
            </div>
        </section>

        <!-- CTA band -->
        <section class="py-20 md:py-28">
            <div class="site-container">
                <div class="tile-sunset panel p-8 sm:p-12 lg:p-14">
                    <h2 v-reveal class="t-h2 max-w-3xl">{{ borrelEnrollmentOpen ? t('about.ctaOpen') : t('about.ctaClosed') }}</h2>
                    <div v-reveal="80" class="mt-8 flex flex-wrap gap-3">
                        <Link href="/edities" class="btn btn-ink btn-lg">
                            {{ t('about.viewAllEditions') }}
                            <PhArrowRight :size="18" weight="bold" aria-hidden="true" />
                        </Link>
                        <Link v-if="borrelEnrollmentOpen" href="/#borrel" class="btn btn-secondary btn-lg">{{ t('home.registerDrinks') }}</Link>
                    </div>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>
