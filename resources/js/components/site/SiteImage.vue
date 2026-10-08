<script setup lang="ts">
import { ref, watch } from 'vue';

/**
 * Image that degrades to a quiet brand placeholder when the source is
 * missing or fails to load, so cards never show broken-image alt text.
 */
const props = withDefaults(
    defineProps<{
        src?: string | null;
        alt: string;
        loading?: 'lazy' | 'eager';
    }>(),
    { src: null, loading: 'lazy' },
);

const failed = ref(false);
watch(
    () => props.src,
    () => (failed.value = false),
);
</script>

<template>
    <img v-if="src && !failed" :src="src" :alt="alt" :loading="loading" decoding="async" @error="failed = true" />
    <div v-else class="flex size-full items-center justify-center bg-surface-2" role="img" :aria-label="alt">
        <img src="/favicon.svg" alt="" class="!size-12 !object-contain opacity-25 grayscale" />
    </div>
</template>
