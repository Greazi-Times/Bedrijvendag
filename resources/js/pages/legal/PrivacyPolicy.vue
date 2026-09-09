<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { useTranslations } from '@/i18n';
import { computed } from 'vue';

const { dateLocale, t } = useTranslations();

interface Policy {
    policyHtml: string | null;
    updatedAt?: string | null;
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const props = defineProps<{
    policy: Policy;
}>();

const updatedAt = computed(() => {
    if (!props.policy.updatedAt) return null;

    const date = new Date(`${props.policy.updatedAt}T00:00:00`);
    return Number.isNaN(date.getTime()) ? props.policy.updatedAt : new Intl.DateTimeFormat(dateLocale.value, { dateStyle: 'long' }).format(date);
});
</script>

<template>
    <AppHeader class="sticky top-0 z-50" />

    <main class="brand-hero relative overflow-hidden px-6 py-14 lg:px-16">
        <div class="relative mx-auto max-w-5xl">
            <div class="brand-card rounded-3xl p-6">
                <div class="flex flex-col gap-1">
                    <p v-if="updatedAt" class="text-sm text-muted-foreground">{{ t('legal.lastModified', { date: updatedAt }) }}</p>
                    <h1 class="text-3xl font-semibold">{{ t('legal.privacy') }}</h1>
                </div>

                <div v-if="policy?.policyHtml" class="prose-m prose mt-6 max-w-none dark:prose-invert" v-html="policy.policyHtml" />
                <p v-else class="mt-5 text-sm text-muted-foreground">{{ t('legal.privacyEmpty') }}</p>
            </div>
        </div>
    </main>

    <AppFooter />
</template>
