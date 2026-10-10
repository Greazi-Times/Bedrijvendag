<script setup lang="ts">
/**
 * Opening band for inner pages: the page headline and an optional aside
 * (actions or facts) on a quiet surface.
 */
type Props = {
    title: string;
    eyebrow?: string | null;
    lead?: string | null;
};

withDefaults(defineProps<Props>(), {
    eyebrow: null,
    lead: null,
});
</script>

<template>
    <section class="bg-intro border-b border-hairline">
        <div class="site-container grid grid-cols-1 gap-8 pt-12 pb-12 md:pt-16 md:pb-14 lg:grid-cols-12 lg:items-end lg:gap-12">
            <div :class="$slots.aside ? 'lg:col-span-8' : 'lg:col-span-10'">
                <p v-if="eyebrow" class="enter t-eyebrow">{{ eyebrow }}</p>
                <h1 class="enter t-h1 text-ink" :class="eyebrow ? 'mt-4' : ''" style="--enter-delay: 60ms">
                    <slot name="title">{{ title }}</slot>
                </h1>
                <p v-if="lead || $slots.lead" class="enter t-lead mt-5 max-w-2xl" style="--enter-delay: 120ms">
                    <slot name="lead">{{ lead }}</slot>
                </p>
                <div v-if="$slots.default" class="enter mt-8" style="--enter-delay: 180ms">
                    <slot />
                </div>
            </div>
            <div v-if="$slots.aside" class="enter lg:col-span-4" style="--enter-delay: 220ms">
                <slot name="aside" />
            </div>
        </div>
    </section>
</template>
