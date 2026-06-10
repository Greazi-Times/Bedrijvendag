<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();

const page = usePage();

const logoSrc = computed(() => page.props.media?.siteLogo ?? '/favicon.svg');
</script>

<template>
    <img :src="logoSrc" :class="className" v-bind="$attrs" alt="Logo" />
</template>
