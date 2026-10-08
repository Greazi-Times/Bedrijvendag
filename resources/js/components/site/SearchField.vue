<script setup lang="ts">
import { PhMagnifyingGlass, PhX } from '@phosphor-icons/vue';
import { useTranslations } from '@/i18n';

defineProps<{
    label: string;
    placeholder?: string;
    id?: string;
}>();

const model = defineModel<string>({ default: '' });
const { t } = useTranslations();
</script>

<template>
    <div class="relative">
        <label :for="id ?? 'search'" class="sr-only">{{ label }}</label>
        <PhMagnifyingGlass :size="18" class="pointer-events-none absolute top-1/2 left-5 -translate-y-1/2 text-ink-muted" aria-hidden="true" />
        <input
            :id="id ?? 'search'"
            v-model="model"
            type="search"
            autocomplete="off"
            spellcheck="false"
            :placeholder="placeholder"
            class="input input-search pr-12 [&::-webkit-search-cancel-button]:hidden"
        />
        <button
            v-if="model"
            type="button"
            class="absolute top-1/2 right-2 flex size-8 -translate-y-1/2 items-center justify-center rounded-[var(--radius-chip)] text-ink-muted transition-colors hover:bg-surface hover:text-ink"
            :aria-label="t('common.clear')"
            @click="model = ''"
        >
            <PhX :size="16" aria-hidden="true" />
        </button>
    </div>
</template>
