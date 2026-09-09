<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Languages } from 'lucide-vue-next';
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
        class="flex h-9 items-center gap-1.5 rounded-xl bg-white/60 px-2.5 text-sm font-semibold ring-1 ring-border/70 transition hover:bg-accent disabled:opacity-60 dark:bg-white/5"
        :aria-label="`${t('language.label')}: ${nextLocale === 'en' ? t('language.english') : t('language.dutch')}`"
        :title="nextLocale === 'en' ? t('language.english') : t('language.dutch')"
        :disabled="changing"
        @click="switchLocale"
    >
        <Languages class="size-4" aria-hidden="true" />
        <span class="uppercase">{{ locale }}</span>
    </button>
</template>
