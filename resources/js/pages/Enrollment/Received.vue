<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { PhCheckCircle } from '@phosphor-icons/vue';

import PageIntro from '@/components/site/PageIntro.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';

defineProps<{ event: { name: string }; ui: Record<string, string> }>();
</script>

<template>
    <Head :title="ui.success_title" />
    <SiteLayout>
        <PageIntro :eyebrow="event.name" :title="ui.success_title" />
        <section class="site-container pt-14 pb-24 md:pt-20 md:pb-32">
            <div class="alert alert-success mx-auto max-w-2xl" role="status">
                <div class="flex items-start gap-3">
                    <PhCheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-success" />
                    <p>{{ ui.success_body }}</p>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>
