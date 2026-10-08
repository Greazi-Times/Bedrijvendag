<script setup lang="ts">
import { PhX } from '@phosphor-icons/vue';
import { DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { useTranslations } from '@/i18n';

defineProps<{
    src: string;
    title: string;
}>();

const open = defineModel<boolean>('open', { default: false });
const { t } = useTranslations();
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-50 bg-[rgb(var(--site-scrim)/0.82)] backdrop-blur-sm data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
            />
            <DialogContent
                class="fixed top-1/2 left-1/2 z-50 w-[calc(100%-2rem)] max-w-5xl -translate-x-1/2 -translate-y-1/2 focus:outline-none data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95"
            >
                <div class="mb-3 flex items-center justify-between gap-4 text-white">
                    <DialogTitle class="text-sm font-medium">{{ title }}</DialogTitle>
                    <DialogDescription class="sr-only">{{ title }}</DialogDescription>
                    <DialogClose class="btn btn-glass btn-icon size-10" :aria-label="t('common.close')">
                        <PhX :size="18" aria-hidden="true" />
                    </DialogClose>
                </div>
                <div class="aspect-video w-full overflow-hidden rounded-[var(--radius-card)] bg-black">
                    <iframe
                        v-if="open"
                        class="size-full"
                        :src="src"
                        :title="title"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                        referrerpolicy="strict-origin-when-cross-origin"
                    ></iframe>
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
