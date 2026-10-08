<script setup lang="ts">
import type { Component } from 'vue';

type Props = {
    title: string;
    text?: string | null;
    icon?: Component | null;
};

withDefaults(defineProps<Props>(), {
    text: null,
    icon: null,
});
</script>

<template>
    <div class="card-muted flex flex-col items-center px-6 py-14 text-center md:py-20">
        <span v-if="icon" class="flex size-12 items-center justify-center rounded-full bg-canvas text-ink shadow-[inset_0_0_0_1px_var(--site-hairline)]">
            <component :is="icon" :size="22" aria-hidden="true" />
        </span>
        <h2 class="t-h4 text-ink" :class="icon ? 'mt-5' : ''">{{ title }}</h2>
        <p v-if="text" class="t-small mt-2 max-w-md">{{ text }}</p>
        <div v-if="$slots.default" class="mt-6 flex flex-wrap justify-center gap-3">
            <slot />
        </div>
    </div>
</template>
