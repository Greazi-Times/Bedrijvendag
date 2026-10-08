<script setup lang="ts">
import { PhCheck, PhX } from '@phosphor-icons/vue';
import { DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { computed, ref } from 'vue';
import { useTranslations } from '@/i18n';

const props = defineProps<{
    educationOptions: string[];
    sectorOptions: string[];
    description: string;
    educationPlaceholder: string;
    sectorPlaceholder: string;
}>();

const open = defineModel<boolean>('open', { default: false });
const educations = defineModel<string[]>('educations', { required: true });
const sectors = defineModel<string[]>('sectors', { required: true });

const { t } = useTranslations();
const educationQuery = ref('');
const sectorQuery = ref('');

const match = (options: string[], query: string) => {
    const q = query.trim().toLowerCase();
    return q ? options.filter((n) => n.toLowerCase().includes(q)) : options;
};

const groups = computed(() => [
    {
        key: 'edu',
        title: t('common.educations'),
        placeholder: props.educationPlaceholder,
        query: educationQuery,
        options: match(props.educationOptions, educationQuery.value),
        selected: educations,
    },
    {
        key: 'sec',
        title: t('common.sectors'),
        placeholder: props.sectorPlaceholder,
        query: sectorQuery,
        options: match(props.sectorOptions, sectorQuery.value),
        selected: sectors,
    },
]);

const activeCount = computed(() => educations.value.length + sectors.value.length);

const toggle = (list: string[], value: string) => (list.includes(value) ? list.filter((item) => item !== value) : [...list, value]);

const clearAll = () => {
    educations.value = [];
    sectors.value = [];
    educationQuery.value = '';
    sectorQuery.value = '';
};
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-50 bg-[rgb(var(--site-scrim)/0.5)] backdrop-blur-[2px] data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
            />
            <DialogContent
                class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-canvas shadow-[-30px_0_80px_-40px_rgb(5_6_9/0.4)] duration-300 focus:outline-none data-[state=closed]:animate-out data-[state=closed]:slide-out-to-right data-[state=open]:animate-in data-[state=open]:slide-in-from-right sm:border-l sm:border-hairline"
            >
                <div class="flex items-start justify-between gap-4 border-b border-hairline px-6 py-5">
                    <div>
                        <DialogTitle class="t-h4 text-ink">{{ t('common.filters') }}</DialogTitle>
                        <DialogDescription class="t-small mt-1">{{ description }}</DialogDescription>
                    </div>
                    <DialogClose class="btn btn-ghost btn-icon -mt-1 -mr-3" :aria-label="t('common.close')">
                        <PhX :size="20" aria-hidden="true" />
                    </DialogClose>
                </div>

                <div class="flex-1 overflow-y-auto overscroll-contain px-6 py-6">
                    <fieldset v-for="group in groups" :key="group.key" class="mb-10 last:mb-0">
                        <legend class="text-sm font-semibold text-ink">{{ group.title }}</legend>
                        <label :for="`filter-search-${group.key}`" class="sr-only">{{ group.placeholder }}</label>
                        <input
                            :id="`filter-search-${group.key}`"
                            v-model="group.query.value"
                            type="search"
                            autocomplete="off"
                            :placeholder="group.placeholder"
                            class="input mt-3 min-h-11 text-[0.9375rem]"
                        />

                        <ul class="mt-3 space-y-1">
                            <li v-for="name in group.options" :key="`${group.key}-${name}`">
                                <label
                                    class="flex min-h-11 cursor-pointer items-center gap-3 rounded-[var(--radius-input)] px-3 py-2 text-[0.9375rem] text-ink transition-colors hover:bg-surface has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-brand"
                                >
                                    <input
                                        type="checkbox"
                                        class="peer sr-only"
                                        :checked="group.selected.value.includes(name)"
                                        @change="group.selected.value = toggle(group.selected.value, name)"
                                    />
                                    <span
                                        class="flex size-5 shrink-0 items-center justify-center rounded-[5px] border border-[color-mix(in_srgb,var(--site-ink)_25%,transparent)] text-transparent transition-colors peer-checked:border-ink peer-checked:bg-ink peer-checked:text-canvas"
                                        aria-hidden="true"
                                    >
                                        <PhCheck :size="12" weight="bold" />
                                    </span>
                                    {{ name }}
                                </label>
                            </li>
                            <li v-if="!group.options.length" class="t-small px-3 py-2">{{ t('common.noResults') }}</li>
                        </ul>
                    </fieldset>
                </div>

                <div class="flex items-center gap-3 border-t border-hairline px-6 py-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                    <button type="button" class="btn btn-secondary" :disabled="!activeCount" @click="clearAll">{{ t('common.clearAll') }}</button>
                    <DialogClose class="btn btn-ink flex-1">
                        {{ t('common.showResults') }}
                    </DialogClose>
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
