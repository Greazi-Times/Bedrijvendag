<script setup lang="ts">
import { PhMoon, PhSun } from '@phosphor-icons/vue';
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import { useTranslations } from '@/i18n';

const { resolvedAppearance, updateAppearance } = useAppearance();
const { t } = useTranslations();

const isDark = computed(() => resolvedAppearance.value === 'dark');
const label = computed(() => (isDark.value ? t('theme.light') : t('theme.dark')));

const toggleTheme = () => {
    updateAppearance(isDark.value ? 'light' : 'dark');
};
</script>

<template>
    <button
        type="button"
        class="inline-flex size-9 cursor-pointer items-center justify-center rounded-[var(--radius-input)] text-ink-muted transition-colors hover:bg-surface hover:text-ink"
        :aria-label="label"
        :title="label"
        @click="toggleTheme"
    >
        <PhSun v-if="isDark" :size="18" aria-hidden="true" />
        <PhMoon v-else :size="18" aria-hidden="true" />
    </button>
</template>
