<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 60" :class="className" v-bind="$attrs">
        <text x="0" y="45" font-family="Arial, sans-serif" font-size="48" font-weight="bold" fill="#c6002a">ATIx</text>
    </svg>
</template>
