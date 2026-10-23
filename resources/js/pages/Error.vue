<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PhArrowLeft, PhArrowRight, PhBuildings, PhCalendarBlank, PhEnvelopeSimple, PhMapTrifold } from '@phosphor-icons/vue';
import { computed } from 'vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import { useTranslations } from '@/i18n';

type Props = {
    status: 403 | 404 | 419;
};

const props = defineProps<Props>();
const { t } = useTranslations();

// One accent per status, all from the site palette; it marks the badge stripe and the last digit.
const accents: Record<Props['status'], string> = {
    403: 'var(--site-brand-ink)',
    404: 'var(--site-brand)',
    419: 'var(--site-brand-blue)',
};

const code = computed(() => String(props.status));
const showSuggestions = computed(() => props.status !== 419);

const suggestions = computed(() => [
    { href: '/bedrijven', icon: PhBuildings, key: 'companies' },
    { href: '/edities', icon: PhCalendarBlank, key: 'editions' },
    { href: '/plattegrond', icon: PhMapTrifold, key: 'map' },
    { href: '/contact', icon: PhEnvelopeSimple, key: 'contact' },
]);

// Back to the form, where a refresh gives a fresh token; reloading would resubmit the expired request.
function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.assign('/');
    }
}
</script>

<template>
    <Head :title="t(`errors.${status}.title`)" />

    <SiteLayout>
        <div class="flex min-h-full flex-col" :style="{ '--error-accent': accents[status] }">
            <section class="hero-ambient">
                <div class="site-container grid grid-cols-1 gap-12 pt-12 pb-16 md:pt-16 md:pb-20 lg:grid-cols-12 lg:items-center lg:gap-14 lg:py-24">
                    <div class="lg:col-span-7">
                        <p class="enter t-eyebrow flex items-center gap-2">
                            <span class="size-2 rounded-full bg-[var(--error-accent)]" aria-hidden="true"></span>
                            {{ t('errors.eyebrow', { code }) }}
                        </p>
                        <h1 class="enter t-h1 mt-4 text-ink" style="--enter-delay: 60ms">{{ t(`errors.${status}.title`) }}</h1>
                        <p class="enter t-lead mt-5 max-w-xl" style="--enter-delay: 120ms">{{ t(`errors.${status}.message`) }}</p>

                        <div class="enter mt-8 flex flex-wrap items-center gap-3" style="--enter-delay: 180ms">
                            <template v-if="status === 419">
                                <button type="button" class="btn btn-primary btn-lg" @click="goBack">
                                    <PhArrowLeft :size="18" weight="bold" aria-hidden="true" />
                                    {{ t('errors.back') }}
                                </button>
                                <Link href="/" class="btn btn-secondary btn-lg">{{ t('errors.home') }}</Link>
                            </template>
                            <template v-else>
                                <Link href="/" class="btn btn-primary btn-lg">
                                    {{ t('errors.home') }}
                                    <PhArrowRight :size="18" weight="bold" aria-hidden="true" />
                                </Link>
                                <Link href="/contact" class="btn btn-secondary btn-lg">{{ t('errors.contact') }}</Link>
                            </template>
                        </div>
                    </div>

                    <!-- The code as a fair visitor badge: decorative, the heading carries the meaning. -->
                    <div class="enter-media lg:col-span-5" aria-hidden="true">
                        <div class="card media-depth w-full max-w-sm overflow-hidden rounded-[var(--radius-panel)] lg:ml-auto">
                            <div class="h-1.5 bg-[var(--error-accent)]"></div>
                            <div class="flex items-center justify-between gap-4 px-6 pt-5">
                                <span class="flex items-center gap-2 text-sm font-semibold tracking-[-0.01em] text-ink" translate="no">
                                    <img src="/favicon.svg" alt="" class="size-5" />
                                    ATIx Bedrijvendag
                                </span>
                                <span class="h-2 w-10 rounded-full bg-surface-2 shadow-[inset_0_0_0_1px_var(--site-hairline)]"></span>
                            </div>
                            <p class="px-6 pt-10 pb-8 text-[clamp(5.5rem,4rem+6vw,8.5rem)] leading-[0.85] font-semibold tracking-[-0.055em] text-ink tabular-nums">
                                {{ code.slice(0, -1) }}<span class="text-[var(--error-accent)]">{{ code.slice(-1) }}</span>
                            </p>
                            <div class="flex items-center justify-between gap-4 border-t border-hairline bg-surface px-6 py-4 text-sm">
                                <span class="text-ink-muted">{{ t('errors.status') }}</span>
                                <span class="font-medium text-ink">{{ t(`errors.${status}.label`) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="showSuggestions" class="border-t border-hairline bg-canvas py-12 md:py-16" aria-labelledby="error-suggestions">
                <div class="site-container">
                    <h2 id="error-suggestions" class="t-h4 text-ink">{{ t('errors.suggestions') }}</h2>
                    <ul class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:gap-4">
                        <li v-for="link in suggestions" :key="link.href">
                            <Link :href="link.href" class="card card-interactive group flex h-full items-start gap-4 p-5">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-surface text-ink shadow-[inset_0_0_0_1px_var(--site-hairline)]">
                                    <component :is="link.icon" :size="20" aria-hidden="true" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-2 font-semibold tracking-[-0.015em] text-ink">
                                        {{ t(`errors.links.${link.key}.title`) }}
                                        <PhArrowRight :size="14" class="text-ink-subtle transition-transform group-hover:translate-x-0.5 group-hover:text-ink" aria-hidden="true" />
                                    </span>
                                    <span class="t-small mt-1 block">{{ t(`errors.links.${link.key}.text`) }}</span>
                                </span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </SiteLayout>
</template>
