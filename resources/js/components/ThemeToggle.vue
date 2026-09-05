<script setup lang="ts">
import { computed } from 'vue';
import { Moon, Sun } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');
const label = computed(() => (isDark.value ? 'Lichte modus inschakelen' : 'Donkere modus inschakelen'));

const toggleTheme = () => {
    updateAppearance(isDark.value ? 'light' : 'dark');
};
</script>

<template>
    <Button
        type="button"
        variant="ghost"
        size="icon"
        class="h-9 w-9 cursor-pointer rounded-xl bg-white/60 ring-1 ring-border/70 dark:bg-white/5"
        :aria-label="label"
        :title="label"
        @click="toggleTheme"
    >
        <Sun v-if="isDark" class="size-5" aria-hidden="true" />
        <Moon v-else class="size-5" aria-hidden="true" />
    </Button>
</template>
