<script setup lang="ts">
import PageIntro from '@/components/site/PageIntro.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import { sanitizeHtml } from '@/lib/sanitize';

defineProps<{
    title: string;
    html?: string | null;
    meta?: string | null;
    emptyText: string;
}>();
</script>

<template>
    <SiteLayout>
        <PageIntro :eyebrow="meta" :title="title" />

        <section class="site-container pt-14 pb-24 md:pt-20 md:pb-32">
            <article class="mx-auto max-w-3xl">
                <div v-if="html" class="rich" v-html="sanitizeHtml(html)" />
                <p v-else class="t-body">{{ emptyText }}</p>
            </article>
        </section>
    </SiteLayout>
</template>
