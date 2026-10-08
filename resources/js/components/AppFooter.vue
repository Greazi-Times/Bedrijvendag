<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { PhArrowRight, PhCheckCircle, PhGithubLogo, PhWarningCircle } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';

import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useTranslations } from '@/i18n';

type FlashType = 'success' | 'error';

const newsletterEmail = ref('');
const newsletterFlash = ref<{ type: FlashType; message: string } | null>(null);
const isSubscribing = ref(false);
const page = usePage();
const { t } = useTranslations();
let newsletterFlashTimeout: number | null = null;

const year = new Date().getFullYear();

const pageLinks = computed(() => [
    { label: t('nav.home'), href: '/' },
    { label: t('footer.events'), href: '/edities' },
    { label: t('nav.companies'), href: '/bedrijven' },
    { label: t('nav.map'), href: '/plattegrond' },
    { label: t('nav.contact'), href: '/contact' },
]);

const infoLinks = computed(() => [
    { label: t('footer.programme'), href: '/edities' },
    { label: t('footer.location'), href: '/contact' },
    { label: t('nav.partners'), href: '/partners' },
    { label: t('nav.forCompanies'), href: '/voor-bedrijven' },
]);

const legalLinks = computed(() => [
    { label: t('footer.privacy'), href: '/privacy-policy' },
    { label: t('footer.terms'), href: '/terms-of-service' },
    { label: t('footer.cookies'), href: '/cookie-policy' },
]);

function setNewsletterFlash(type: FlashType, message: string) {
    newsletterFlash.value = { type, message };
    if (newsletterFlashTimeout) window.clearTimeout(newsletterFlashTimeout);
    newsletterFlashTimeout = window.setTimeout(() => {
        newsletterFlash.value = null;
        newsletterFlashTimeout = null;
    }, 5000);
}

async function submitNewsletter() {
    const email = newsletterEmail.value.trim();
    if (!email) {
        setNewsletterFlash('error', t('footer.emailRequired'));
        return;
    }

    isSubscribing.value = true;

    try {
        const csrf = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;

        const res = await fetch('/newsletter/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(csrf?.content ? { 'X-CSRF-TOKEN': csrf.content } : {}),
            },
            body: JSON.stringify({ email }),
        });

        if (!res.ok) {
            const data = await res.json().catch(() => null);
            const msg = data?.message || t('footer.subscribeFailed');
            setNewsletterFlash('error', msg);
            return;
        }

        newsletterEmail.value = '';
        setNewsletterFlash('success', t('footer.subscribeSuccess'));
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (e) {
        setNewsletterFlash('error', t('footer.subscribeFailed'));
    } finally {
        isSubscribing.value = false;
    }
}
</script>

<template>
    <footer class="theme-dark relative isolate overflow-hidden">
        <div class="grid-lines opacity-60" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -top-40 left-1/2 -z-10 h-80 w-[60rem] -translate-x-1/2 rounded-full bg-brand/20 blur-[120px]" aria-hidden="true"></div>

        <div class="site-container relative pt-16 md:pt-24">
            <!-- Newsletter call-out -->
            <div class="grid grid-cols-1 gap-10 border-b border-hairline pb-16 md:pb-20 lg:grid-cols-12 lg:items-end">
                <div class="lg:col-span-7">
                    <h2 class="t-h2 text-ink">{{ t('about.ctaTitle') }}</h2>
                    <p class="t-lead mt-5 max-w-lg">{{ t('footer.newsletterDescription') }}</p>
                </div>
                <div class="lg:col-span-5">
                    <form class="flex gap-2 rounded-[var(--radius-card)] bg-white/[0.06] p-1.5 ring-1 ring-white/10 focus-within:ring-brand/60" novalidate @submit.prevent="submitNewsletter">
                        <label for="newsletter-email" class="sr-only">{{ t('footer.emailPlaceholder') }}</label>
                        <input
                            id="newsletter-email"
                            v-model="newsletterEmail"
                            type="email"
                            name="email"
                            autocomplete="email"
                            spellcheck="false"
                            :placeholder="t('footer.emailPlaceholder')"
                            class="min-h-12 w-full min-w-0 bg-transparent px-5 text-base text-ink placeholder:text-ink-subtle focus:outline-none"
                            :aria-invalid="newsletterFlash?.type === 'error' ? 'true' : undefined"
                            aria-describedby="newsletter-feedback"
                            required
                        />
                        <button type="submit" class="btn btn-primary shrink-0" :disabled="isSubscribing">
                            {{ t('footer.newsletter') }}
                            <PhArrowRight :size="16" weight="bold" aria-hidden="true" />
                        </button>
                    </form>

                    <div id="newsletter-feedback" aria-live="polite">
                        <p
                            v-if="newsletterFlash"
                            class="mt-3 flex items-center gap-2 px-5 text-sm font-medium"
                            :class="newsletterFlash.type === 'success' ? 'text-success' : 'text-danger'"
                        >
                            <PhCheckCircle v-if="newsletterFlash.type === 'success'" :size="16" weight="fill" aria-hidden="true" />
                            <PhWarningCircle v-else :size="16" weight="fill" aria-hidden="true" />
                            {{ newsletterFlash.message }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Links -->
            <div class="grid grid-cols-1 gap-12 py-16 md:grid-cols-12 md:gap-8">
                <div class="md:col-span-12 lg:col-span-5">
                    <Link href="/" class="inline-flex items-center gap-2.5">
                        <AppLogoIcon class="size-9" alt="" />
                        <span class="text-base font-semibold tracking-[-0.02em] text-ink" translate="no">ATIx Bedrijvendag</span>
                    </Link>
                    <p class="t-small mt-5 max-w-sm">{{ t('footer.description') }}</p>
                </div>

                <nav class="md:col-span-4 lg:col-span-2" :aria-label="t('footer.pages')">
                    <h2 class="text-sm font-semibold text-ink">{{ t('footer.pages') }}</h2>
                    <ul class="mt-4 space-y-3">
                        <li v-for="link in pageLinks" :key="link.href + link.label">
                            <Link :href="link.href" class="text-sm text-ink-muted transition-colors hover:text-brand">{{ link.label }}</Link>
                        </li>
                    </ul>
                </nav>

                <nav class="md:col-span-4 lg:col-span-2" :aria-label="t('footer.information')">
                    <h2 class="text-sm font-semibold text-ink">{{ t('footer.information') }}</h2>
                    <ul class="mt-4 space-y-3">
                        <li v-for="link in infoLinks" :key="link.href + link.label">
                            <Link :href="link.href" class="text-sm text-ink-muted transition-colors hover:text-brand">{{ link.label }}</Link>
                        </li>
                        <li>
                            <a href="/dashboard" class="text-sm text-ink-muted transition-colors hover:text-brand">Dashboard</a>
                        </li>
                    </ul>
                </nav>

                <nav class="md:col-span-4 lg:col-span-3" :aria-label="t('footer.legal')">
                    <h2 class="text-sm font-semibold text-ink">{{ t('footer.legal') }}</h2>
                    <ul class="mt-4 space-y-3">
                        <li v-for="link in legalLinks" :key="link.href">
                            <Link :href="link.href" class="text-sm text-ink-muted transition-colors hover:text-brand">{{ link.label }}</Link>
                        </li>
                        <li>
                            <a
                                href="https://github.com/Greazi-Times/Bedrijvendag"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 text-sm text-ink-muted transition-colors hover:text-brand"
                            >
                                <PhGithubLogo :size="15" aria-hidden="true" />
                                {{ t('nav.repository') }}
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Oversized wordmark -->
        <div class="relative overflow-hidden" aria-hidden="true">
            <p
                class="site-container t-mega bg-[linear-gradient(180deg,rgb(255_255_255/0.16)_0%,rgb(255_255_255/0.02)_85%)] bg-clip-text pb-[0.06em] text-center whitespace-nowrap text-transparent select-none"
                style="font-size: clamp(3rem, 0.4rem + 13.2vw, 13.5rem); letter-spacing: -0.05em"
            >
                Bedrijvendag
            </p>
        </div>

        <div class="site-container relative flex flex-col gap-3 border-t border-hairline py-6 text-[0.8125rem] text-ink-muted md:flex-row md:items-center md:justify-between">
            <p>© {{ year }} ATIx Bedrijvendag. {{ t('footer.rights') }}</p>
            <p v-if="page.props.deploymentVersion" class="t-mono text-xs text-ink-subtle">{{ page.props.deploymentVersion }}</p>
        </div>
    </footer>
</template>
