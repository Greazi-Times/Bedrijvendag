<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { PhCheckCircle, PhWarningCircle } from '@phosphor-icons/vue';

import PageIntro from '@/components/site/PageIntro.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';

const props = defineProps<{
    state: 'ready' | 'verified' | 'invalid' | 'expired';
    company?: string | null;
    event?: string | null;
    name?: string | null;
    url: string;
    ui: Record<string, string>;
}>();

const form = useForm({});
const confirm = () => form.post(props.url);
</script>

<template>
    <Head :title="ui.title" />

    <SiteLayout>
        <PageIntro :eyebrow="event ?? null" :title="ui.title" />

        <section class="site-container pt-14 pb-24 md:pt-20 md:pb-32">
            <div class="mx-auto max-w-2xl">
                <div v-if="state === 'ready'" class="card p-6 sm:p-10">
                    <p class="text-sm text-ink-muted">{{ ui.intro.replace(':company', company ?? '').replace(':event', event ?? '') }}</p>
                    <button type="button" class="btn btn-primary mt-6" :disabled="form.processing" @click="confirm">{{ ui.confirm }}</button>
                </div>
                <div v-else-if="state === 'verified'" class="alert alert-success" role="status">
                    <div class="flex items-start gap-3">
                        <PhCheckCircle class="mt-0.5 h-5 w-5 shrink-0 text-success" />
                        <p>{{ ui.done }}</p>
                    </div>
                </div>
                <div v-else class="alert" role="status">
                    <div class="flex items-start gap-3">
                        <PhWarningCircle class="mt-0.5 h-5 w-5 shrink-0" />
                        <p>{{ state === 'expired' ? ui.expired : ui.invalid }}</p>
                    </div>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>
