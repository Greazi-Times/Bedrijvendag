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

const updatedAt = computed(() => {
    if (!props.policy.updatedAt) return null;

    const date = new Date(`${props.policy.updatedAt}T00:00:00`);
    return Number.isNaN(date.getTime()) ? props.policy.updatedAt : new Intl.DateTimeFormat(dateLocale.value, { dateStyle: 'long' }).format(date);
});
</script>

<template>
    <Head :title="t('legal.privacy')" />
    <LegalPage
        :title="t('legal.privacy')"
        :html="policy.policyHtml"
        :meta="updatedAt ? t('legal.lastModified', { date: updatedAt }) : null"
        :empty-text="t('legal.privacyEmpty')"
    />
</template>
