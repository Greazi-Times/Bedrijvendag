<script setup lang="ts">
import { PhX } from '@phosphor-icons/vue';
import { DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { useTranslations } from '@/i18n';

/**
 * Shared modal shell: bottom sheet on phones, centered panel from sm up.
 * Focus trap, Escape and scroll locking come from reka-ui.
 */
defineProps<{
    title: string;
    meta?: string | null;
    size?: 'md' | 'lg';
}>();

const open = defineModel<boolean>('open', { default: false });
const { t } = useTranslations();
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-50 bg-[rgb(var(--site-scrim)/0.6)] backdrop-blur-sm data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
            />
            <DialogContent
                class="fixed inset-x-0 bottom-0 z-50 flex max-h-[92dvh] flex-col overflow-hidden rounded-t-[var(--radius-panel)] bg-canvas shadow-[0_40px_120px_-40px_rgb(5_6_9/0.6)] duration-300 focus:outline-none data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:slide-out-to-bottom-4 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:slide-in-from-bottom-4 sm:top-1/2 sm:right-auto sm:bottom-auto sm:left-1/2 sm:max-h-[85dvh] sm:w-[calc(100%-2rem)] sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-[var(--radius-panel)] dark:bg-surface"
                :class="size === 'lg' ? 'sm:max-w-3xl' : 'sm:max-w-2xl'"
            >
                <div class="flex items-start gap-4 px-6 pt-6 sm:px-8 sm:pt-8">
                    <div class="min-w-0 flex-1">
                        <p v-if="meta" class="t-small">{{ meta }}</p>
                        <DialogTitle class="t-h3 text-ink" :class="meta ? 'mt-1' : ''">{{ title }}</DialogTitle>
                    </div>
                    <DialogClose class="btn btn-ghost btn-icon -mt-2 -mr-3 shrink-0" :aria-label="t('common.close')">
                        <PhX :size="20" aria-hidden="true" />
                    </DialogClose>
                </div>

                <DialogDescription as="div" class="flex-1 overflow-y-auto overscroll-contain px-6 py-6 sm:px-8">
                    <slot />
                </DialogDescription>

                <div v-if="$slots.footer" class="border-t border-hairline px-6 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:px-8">
                    <slot name="footer" />
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
