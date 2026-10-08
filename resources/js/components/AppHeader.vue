<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { PhArrowUpRight, PhGithubLogo, PhList, PhMapTrifold, PhX } from '@phosphor-icons/vue';
import { DialogClose } from 'reka-ui';
import { computed, onBeforeUnmount, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Sheet, SheetContent, SheetDescription, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTranslations } from '@/i18n';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { currentUrl } = useCurrentUrl();
const { t } = useTranslations();

const repositoryUrl = 'https://github.com/Greazi-Times/Bedrijvendag';
const isMenuOpen = ref(false);

// Close the mobile menu whenever Inertia navigates.
const removeNavigateListener = router.on('navigate', () => {
    isMenuOpen.value = false;
});
onBeforeUnmount(() => removeNavigateListener());

const mapItem = computed<NavItem>(() => ({ title: t('nav.map'), href: '/plattegrond' }));

const mainNavItems = computed<NavItem[]>(() => [
    { title: t('nav.companies'), href: '/bedrijven' },
    { title: t('nav.forCompanies'), href: '/voor-bedrijven' },
    { title: t('nav.partners'), href: '/partners' },
    { title: t('nav.editions'), href: '/edities' },
    { title: t('nav.about'), href: '/over-ons' },
    { title: t('nav.contact'), href: '/contact' },
]);

// A section stays active on its detail pages too (e.g. /edities/12).
const isActive = (href: NavItem['href']) => {
    const path = String(href);
    return currentUrl.value === path || currentUrl.value.startsWith(`${path}/`);
};
</script>

<template>
    <header class="sticky top-0 z-40">
        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-[var(--radius-input)] focus:bg-ink focus:px-4 focus:py-2 focus:text-sm focus:text-canvas"
        >
            {{ t('nav.skip') }}
        </a>

        <div class="border-b border-hairline bg-canvas/90 backdrop-blur-xl backdrop-saturate-150">
            <div class="site-container flex h-16 items-center gap-6">
                <Link href="/" class="-ml-1 flex min-w-0 items-center gap-2.5 rounded-[var(--radius-input)] py-1 pr-2 pl-1" :aria-label="`ATIx Bedrijvendag, ${t('nav.home')}`">
                    <AppLogoIcon class="size-8 shrink-0" alt="" />
                    <span class="truncate text-[0.9375rem] font-semibold tracking-[-0.02em] text-ink lg:max-xl:hidden" translate="no">ATIx Bedrijvendag</span>
                </Link>

                <nav :aria-label="t('nav.menu')" class="hidden flex-1 justify-center lg:flex">
                    <ul class="flex items-center">
                        <li v-for="item in mainNavItems" :key="item.title">
                            <Link
                                :href="item.href"
                                :aria-current="isActive(item.href) ? 'page' : undefined"
                                class="inline-flex h-9 items-center rounded-[var(--radius-input)] px-3 text-sm font-medium whitespace-nowrap text-ink-muted transition-colors hover:bg-surface hover:text-ink aria-[current=page]:bg-surface aria-[current=page]:text-ink"
                            >
                                {{ item.title }}
                            </Link>
                        </li>
                    </ul>
                </nav>

                <div class="ml-auto flex items-center gap-1 lg:ml-0">
                    <LanguageSwitcher />
                    <ThemeToggle class="max-sm:hidden" />
                    <a
                        :href="repositoryUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hidden size-9 items-center justify-center rounded-[var(--radius-input)] text-ink-muted transition-colors hover:bg-surface hover:text-ink xl:inline-flex"
                        :aria-label="t('nav.repository')"
                        :title="t('nav.repository')"
                    >
                        <PhGithubLogo :size="18" aria-hidden="true" />
                    </a>

                    <Link :href="mapItem.href" :aria-current="isActive(mapItem.href) ? 'page' : undefined" class="btn btn-primary btn-sm ml-2 hidden lg:inline-flex">
                        <PhMapTrifold :size="16" weight="bold" aria-hidden="true" />
                        {{ mapItem.title }}
                    </Link>

                    <Sheet v-model:open="isMenuOpen">
                        <SheetTrigger as-child>
                            <button type="button" class="btn btn-ghost btn-icon -mr-2 lg:hidden" :aria-label="t('nav.openMenu')">
                                <PhList :size="22" aria-hidden="true" />
                            </button>
                        </SheetTrigger>
                        <SheetContent side="top" class="h-[100dvh] gap-0 border-0 bg-canvas p-0 shadow-none [&>button:last-child]:hidden">
                            <SheetTitle class="sr-only">{{ t('nav.menu') }}</SheetTitle>
                            <SheetDescription class="sr-only">ATIx Bedrijvendag</SheetDescription>

                            <div class="site-container flex h-16 shrink-0 items-center justify-between border-b border-hairline">
                                <Link href="/" class="flex items-center gap-2.5">
                                    <AppLogoIcon class="size-8 shrink-0" alt="" />
                                    <span class="text-[0.9375rem] font-semibold tracking-[-0.02em] text-ink" translate="no">ATIx Bedrijvendag</span>
                                </Link>
                                <DialogClose class="btn btn-ghost btn-icon -mr-2" :aria-label="t('common.close')">
                                    <PhX :size="22" aria-hidden="true" />
                                </DialogClose>
                            </div>

                            <nav :aria-label="t('nav.menu')" class="site-container flex-1 overflow-y-auto py-6">
                                <ul class="divide-y divide-hairline">
                                    <li>
                                        <Link
                                            href="/"
                                            :aria-current="currentUrl === '/' ? 'page' : undefined"
                                            class="flex items-center justify-between py-4 text-[1.625rem] font-semibold tracking-[-0.03em] text-ink-muted transition-colors hover:text-ink aria-[current=page]:text-ink"
                                        >
                                            {{ t('nav.home') }}
                                        </Link>
                                    </li>
                                    <li v-for="item in [mapItem, ...mainNavItems]" :key="item.title">
                                        <Link
                                            :href="item.href"
                                            :aria-current="isActive(item.href) ? 'page' : undefined"
                                            class="flex items-center justify-between py-4 text-[1.625rem] font-semibold tracking-[-0.03em] text-ink-muted transition-colors hover:text-ink aria-[current=page]:text-ink"
                                        >
                                            {{ item.title }}
                                            <span v-if="isActive(item.href)" class="size-2 rounded-full bg-brand" aria-hidden="true"></span>
                                        </Link>
                                    </li>
                                </ul>
                            </nav>

                            <div class="site-container flex shrink-0 items-center justify-between border-t border-hairline py-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                                <a
                                    :href="repositoryUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 text-sm font-medium text-ink-muted hover:text-ink"
                                >
                                    <PhGithubLogo :size="18" aria-hidden="true" />
                                    {{ t('nav.repository') }}
                                    <PhArrowUpRight :size="14" aria-hidden="true" />
                                </a>
                                <div class="flex items-center gap-1">
                                    <LanguageSwitcher />
                                    <ThemeToggle />
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </div>

        <div v-if="props.breadcrumbs.length > 1" class="border-b border-hairline bg-canvas">
            <div class="site-container flex h-11 items-center text-ink-muted">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </header>
</template>
