<script setup lang="ts">
import { PhArrowUpRight, PhX } from '@phosphor-icons/vue';
import { DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { computed, ref } from 'vue';
import { useTranslations } from '@/i18n';
import { sanitizeHtml } from '@/lib/sanitize';
import type { CompanyDialogData } from '@/types/site';

const props = defineProps<{
    company: CompanyDialogData | null;
}>();

const open = defineModel<boolean>('open', { default: false });
const { t } = useTranslations();
const logoFailed = ref(false);
const educations = computed(() => props.company?.educations ?? []);
const sectors = computed(() => props.company?.sectors ?? []);
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-50 bg-[rgb(var(--site-scrim)/0.6)] backdrop-blur-sm data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
            />
            <DialogContent
                v-if="company"
                class="fixed inset-x-0 bottom-0 z-50 flex max-h-[92dvh] flex-col overflow-hidden rounded-t-[var(--radius-panel)] bg-canvas shadow-[0_40px_120px_-40px_rgb(5_6_9/0.6)] duration-300 focus:outline-none data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:slide-out-to-bottom-4 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:slide-in-from-bottom-4 sm:top-1/2 sm:right-auto sm:bottom-auto sm:left-1/2 sm:max-h-[85dvh] sm:w-[calc(100%-2rem)] sm:max-w-2xl sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-[var(--radius-panel)] dark:bg-surface"
                @open-auto-focus="logoFailed = false"
            >
                <div class="flex items-start gap-5 px-6 pt-6 sm:px-8 sm:pt-8">
                    <div class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-[var(--radius-card)] bg-white p-2 ring-1 ring-hairline sm:size-20">
                        <img
                            v-if="company.logoUrl && !logoFailed"
                            :src="company.logoUrl"
                            :alt="company.name"
                            class="size-full object-contain"
                            decoding="async"
                            @error="logoFailed = true"
                        />
                        <span v-else class="text-xl font-semibold text-[#545a68]" aria-hidden="true">{{ company.name.charAt(0) }}</span>
                    </div>
                    <div class="min-w-0 flex-1 pt-1">
                        <p class="t-small">
                            {{ company.kind }}<template v-if="company.stand"> · {{ t('common.stand') }} {{ company.stand }}</template>
                        </p>
                        <DialogTitle class="t-h3 mt-1 text-ink">{{ company.name }}</DialogTitle>
                    </div>
                    <DialogClose class="btn btn-ghost btn-icon -mt-2 -mr-3 shrink-0" :aria-label="t('common.close')">
                        <PhX :size="20" aria-hidden="true" />
                    </DialogClose>
                </div>

                <div class="flex-1 overflow-y-auto overscroll-contain px-6 pt-6 pb-8 sm:px-8">
                    <!-- Programmes first: the thing students scan for -->
                    <section
                        v-if="educations.length || company.showSectors !== false"
                        aria-labelledby="dialog-educations"
                        class="rounded-[var(--radius-card)] bg-surface p-4 sm:p-5"
                    >
                        <h3 id="dialog-educations" class="text-sm font-semibold text-ink">{{ t('common.educations') }}</h3>
                        <ul v-if="educations.length" class="mt-3 flex flex-wrap gap-2">
                            <li v-for="n in educations" :key="'edu-' + n" class="chip chip-brand">{{ n }}</li>
                        </ul>
                        <p v-else class="t-small mt-1">{{ t('common.noEducations') }}</p>
                    </section>

                    <DialogDescription as="div" :class="educations.length || company.showSectors !== false ? 'mt-6' : ''">
                        <div v-if="company.description" class="rich" v-html="sanitizeHtml(company.description)"></div>
                        <p v-else class="t-body">{{ t('common.noDescription') }}</p>
                    </DialogDescription>

                    <section v-if="company.showSectors !== false && sectors.length" aria-labelledby="dialog-sectors" class="mt-8 border-t border-hairline pt-5">
                        <h3 id="dialog-sectors" class="t-small font-medium">{{ t('common.sectors') }}</h3>
                        <ul class="mt-2.5 flex flex-wrap gap-2">
                            <li v-for="n in sectors" :key="'sec-' + n" class="chip">{{ n }}</li>
                        </ul>
                    </section>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-hairline px-6 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:px-8">
                    <a v-if="company.websiteUrl" :href="company.websiteUrl" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                        {{ t('common.website') }}
                        <PhArrowUpRight :size="16" weight="bold" aria-hidden="true" />
                    </a>
                    <span v-else class="t-small">{{ t('common.noWebsite') }}</span>
                    <DialogClose class="btn btn-secondary">{{ t('common.close') }}</DialogClose>
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
