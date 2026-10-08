<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
</script>

<template>
    <div class="flex min-h-[100dvh] flex-col bg-canvas">
        <AppHeader />
        <main id="main" tabindex="-1" class="flex-1 focus:outline-none">
            <slot />
        </main>
        <AppFooter />
    </div>
</template>
