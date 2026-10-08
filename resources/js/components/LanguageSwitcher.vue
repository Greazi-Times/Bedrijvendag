<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { PhTranslate } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import { useTranslations } from '@/i18n';

const page = usePage();
const { locale, t } = useTranslations();
const changing = ref(false);
const nextLocale = computed(() => (locale.value === 'nl' ? 'en' : 'nl'));

function switchLocale() {
    changing.value = true;
    router.post(
        '/locale',
        { locale: nextLocale.value },
        {
            preserveScroll: true,
            onFinish: () => {
                changing.value = false;
                document.documentElement.lang = String(page.props.locale);
            },
        },
    );
}
</script>

<template>
    <button
        type="button"
        class="inline-flex h-9 items-center gap-1.5 rounded-[var(--radius-input)] px-2.5 text-[0.8125rem] font-semibold text-ink-muted transition-colors hover:bg-surface hover:text-ink disabled:opacity-60"
        :aria-label="`${t('language.label')}: ${nextLocale === 'en' ? t('language.english') : t('language.dutch')}`"
        :title="nextLocale === 'en' ? t('language.english') : t('language.dutch')"
        :disabled="changing"
        @click="switchLocale"
    >
        <PhTranslate :size="17" aria-hidden="true" />
        <span class="uppercase">{{ locale }}</span>
    </button>
</template>
