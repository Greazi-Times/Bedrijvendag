<script setup lang="ts">
import { PhX } from '@phosphor-icons/vue';
import { useTranslations } from '@/i18n';

const educations = defineModel<string[]>('educations', { required: true });
const sectors = defineModel<string[]>('sectors', { required: true });
const { t } = useTranslations();

const remove = (list: string[], value: string) => list.filter((item) => item !== value);
</script>

<template>
    <ul v-if="educations.length || sectors.length" class="flex flex-wrap gap-2" :aria-label="t('common.filters')">
        <li v-for="name in educations" :key="'edu-' + name">
            <button type="button" class="chip chip-brand pr-2 transition-opacity hover:opacity-80" @click="educations = remove(educations, name)">
                {{ name }}
                <PhX :size="12" weight="bold" aria-hidden="true" />
                <span class="sr-only">{{ t('companyProfile.remove', { name }) }}</span>
            </button>
        </li>
        <li v-for="name in sectors" :key="'sec-' + name">
            <button type="button" class="chip pr-2 transition-opacity hover:opacity-80" @click="sectors = remove(sectors, name)">
                {{ name }}
                <PhX :size="12" weight="bold" aria-hidden="true" />
                <span class="sr-only">{{ t('companyProfile.remove', { name }) }}</span>
            </button>
        </li>
    </ul>
</template>
