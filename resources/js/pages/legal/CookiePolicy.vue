<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import LegalPage from '@/components/site/LegalPage.vue';
import { useTranslations } from '@/i18n';

const { dateLocale, t } = useTranslations();

interface Policy {
    policyHtml: string | null;
    updatedAt?: string | null;
}

const props = defineProps<{
    policy: Policy;
}>();

const policy = props.policy;

const updatedAt = computed(() => {
    if (!policy.updatedAt) return null;

    const date = new Date(`${policy.updatedAt}T00:00:00`);
    return Number.isNaN(date.getTime()) ? policy.updatedAt : new Intl.DateTimeFormat(dateLocale.value, { dateStyle: 'long' }).format(date);
});
</script>

<template>
    <Head :title="t('legal.cookies')" />
    <LegalPage
        :title="t('legal.cookies')"
        :html="policy.policyHtml"
        :meta="updatedAt ? t('legal.lastModified', { date: updatedAt }) : null"
        :empty-text="t('legal.cookiesEmpty')"
    />
</template>
