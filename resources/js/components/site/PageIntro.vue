<script setup lang="ts">
/**
 * Opening band for inner pages: a soft brand glow over a faint grid, the
 * page headline and an optional aside (actions or facts).
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
    <section class="relative isolate overflow-hidden border-b border-hairline bg-surface">
        <div class="aurora aurora-soft" aria-hidden="true"></div>
        <div class="grid-lines grid-lines-ink" aria-hidden="true"></div>

        <div class="site-container relative grid grid-cols-1 gap-10 pt-14 pb-14 md:pt-20 md:pb-16 lg:grid-cols-12 lg:items-end lg:gap-12">
            <div :class="$slots.aside ? 'lg:col-span-8' : 'lg:col-span-10'">
                <p v-if="eyebrow" class="enter chip chip-brand">{{ eyebrow }}</p>
                <h1 class="enter t-h1 text-ink" :class="eyebrow ? 'mt-6' : ''" style="--enter-delay: 60ms">
                    <slot name="title">{{ title }}</slot>
                </h1>
                <p v-if="lead || $slots.lead" class="enter t-lead mt-6 max-w-2xl" style="--enter-delay: 120ms">
                    <slot name="lead">{{ lead }}</slot>
                </p>
                <div v-if="$slots.default" class="enter mt-9" style="--enter-delay: 180ms">
                    <slot />
                </div>
            </div>
            <div v-if="$slots.aside" class="enter lg:col-span-4" style="--enter-delay: 220ms">
                <slot name="aside" />
            </div>
        </div>
    </section>
</template>
