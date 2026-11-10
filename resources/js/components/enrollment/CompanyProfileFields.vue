<script lang="ts">
export type ProfileFields = {
    name: string;
    /** null = the description was not touched; the server then keeps what is stored */
    description: string | null;
    website_url: string;
    education_ids: number[];
    sector_ids: number[];
    new_sector_names: string[];
    logo: File | null;
};
</script>

<script setup lang="ts">
import { PhPlus, PhX } from '@phosphor-icons/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

import RichTextEditor from '@/components/site/RichTextEditor.vue';
import { useTranslations } from '@/i18n';

/**
 * The editable company profile fields, the same ones /bedrijf-profiel offers (name, rich description, website, logo,
 * educations, sectors and proposed new sectors), with the same wording and the same server-side rules.
 */
const props = defineProps<{
    current: { description_html: string; logo_url: string | null };
    initialDescription?: string | null;
    options: { educations: { id: number; name: string }[]; sectors: { id: number; name: string }[] };
    errors: Record<string, string>;
    prefix: string;
}>();

const model = defineModel<ProfileFields>({ required: true });
const state = computed(() => model.value);
const { t } = useTranslations();

const err = (field: string) => props.errors[`${props.prefix}${field}`];
const idFor = (field: string) => `${props.prefix.replace(/\./g, '-')}${field}`;

// --- description: only counts as changed when the editor content differs from what it first reported ---
const editorValue = ref(props.initialDescription ?? props.current.description_html ?? '');
const baseline = ref<string | null>(null);
const onReady = (html: string) => {
    baseline.value = html;
};
watch(editorValue, (html) => {
    state.value.description = baseline.value !== null && html === baseline.value ? null : html;
});

// --- logo ---
const logoPreview = ref<string | null>(props.current.logo_url);
function setPreview(url: string | null) {
    if (logoPreview.value?.startsWith('blob:')) URL.revokeObjectURL(logoPreview.value);
    logoPreview.value = url;
}
function onLogo(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (!file) return;
    state.value.logo = file;
    setPreview(URL.createObjectURL(file));
}
function undoLogo() {
    state.value.logo = null;
    setPreview(props.current.logo_url);
}
onBeforeUnmount(() => setPreview(null));

// --- educations and sectors ---
const toggle = (list: 'education_ids' | 'sector_ids', id: number) => {
    const values = state.value[list];
    state.value[list] = values.includes(id) ? values.filter((v) => v !== id) : [...values, id];
};

const sectorSearch = ref('');
const filteredSectors = computed(() => {
    const q = sectorSearch.value.trim().toLowerCase();
    return q ? props.options.sectors.filter((s) => s.name.toLowerCase().includes(q)) : props.options.sectors;
});
const normalized = (value: string) => value.replace(/\s+/g, ' ').trim();
const canAddSector = computed(() => {
    const name = normalized(sectorSearch.value).toLowerCase();
    if (!name) return false;
    return (
        !props.options.sectors.some((s) => s.name.trim().toLowerCase() === name) &&
        !state.value.new_sector_names.some((s) => s.toLowerCase() === name) &&
        state.value.new_sector_names.length < 10
    );
});
function addSector() {
    if (!canAddSector.value) return;
    state.value.new_sector_names = [...state.value.new_sector_names, normalized(sectorSearch.value)];
    sectorSearch.value = '';
}
const removeSector = (name: string) => (state.value.new_sector_names = state.value.new_sector_names.filter((s) => s !== name));
</script>

<template>
    <div class="grid gap-6">
        <div>
            <label :for="idFor('name')" class="field-label mb-2 block">{{ t('companyProfile.companyName') }}</label>
            <input :id="idFor('name')" v-model="state.name" type="text" maxlength="255" class="input" :aria-invalid="!!err('name')" />
            <p v-if="err('name')" class="field-error mt-2" role="alert">{{ err('name') }}</p>
        </div>

        <div>
            <label :for="idFor('description')" class="field-label mb-2 block">{{ t('common.description') }}</label>
            <p :id="idFor('description-help')" class="mb-2 text-xs text-ink-muted">{{ t('companyProfile.editorHelp') }}</p>
            <RichTextEditor
                :id="idFor('description')"
                v-model="editorValue"
                :placeholder="t('companyProfile.descriptionPlaceholder')"
                :described-by="idFor('description-help')"
                :invalid="!!err('description')"
                @ready="onReady"
            />
            <p v-if="err('description')" class="field-error mt-2" role="alert">{{ err('description') }}</p>
        </div>

        <div>
            <label :for="idFor('website_url')" class="field-label mb-2 block">{{ t('common.website') }}</label>
            <input :id="idFor('website_url')" v-model="state.website_url" type="url" placeholder="https://" class="input" :aria-invalid="!!err('website_url')" />
            <p v-if="err('website_url')" class="field-error mt-2" role="alert">{{ err('website_url') }}</p>
        </div>

        <div>
            <label :for="idFor('logo')" class="field-label mb-2 block">{{ t('companyProfile.logo') }}</label>
            <div class="flex flex-wrap items-center gap-4">
                <img v-if="logoPreview" :src="logoPreview" alt="" class="h-16 w-16 rounded-[var(--radius-input)] bg-canvas object-contain ring-1 ring-hairline" />
                <input :id="idFor('logo')" type="file" accept=".jpg,.jpeg,.png,.webp,.gif" class="input max-w-sm" :aria-invalid="!!err('logo')" @change="onLogo" />
                <button v-if="state.logo" type="button" class="text-xs font-semibold underline" @click="undoLogo">{{ t('companyProfile.undoLogo') }}</button>
            </div>
            <p v-if="state.logo" class="mt-1 text-xs text-ink-muted">{{ t('companyProfile.newLogoSelected', { name: state.logo.name }) }}</p>
            <p v-if="err('logo')" class="field-error mt-2" role="alert">{{ err('logo') }}</p>
        </div>

        <fieldset>
            <legend class="field-label mb-1">{{ t('common.educations') }}</legend>
            <p class="mb-2 text-xs text-ink-muted">{{ t('companyProfile.sectionEducationsHelp') }}</p>
            <div class="grid gap-2 sm:grid-cols-2">
                <label v-for="o in options.educations" :key="o.id" class="flex items-center gap-3 rounded-[var(--radius-input)] bg-canvas px-4 py-2 text-sm ring-1 ring-hairline">
                    <input type="checkbox" class="h-4 w-4" :checked="state.education_ids.includes(o.id)" @change="toggle('education_ids', o.id)" />{{ o.name }}
                </label>
            </div>
            <p v-if="err('education_ids')" class="field-error mt-2" role="alert">{{ err('education_ids') }}</p>
        </fieldset>

        <fieldset>
            <legend class="field-label mb-2">{{ t('common.sectors') }}</legend>
            <input v-model="sectorSearch" type="search" :placeholder="t('companyProfile.searchSector')" class="input mb-3" @keydown.enter.prevent="addSector" />
            <div class="grid max-h-56 gap-2 overflow-y-auto sm:grid-cols-2">
                <label v-for="o in filteredSectors" :key="o.id" class="flex items-center gap-3 rounded-[var(--radius-input)] bg-canvas px-4 py-2 text-sm ring-1 ring-hairline">
                    <input type="checkbox" class="h-4 w-4" :checked="state.sector_ids.includes(o.id)" @change="toggle('sector_ids', o.id)" />{{ o.name }}
                </label>
                <p v-if="!filteredSectors.length" class="text-sm text-ink-muted">{{ t('companyProfile.noSector') }}</p>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button type="button" class="btn btn-secondary" :disabled="!canAddSector" @click="addSector"><PhPlus class="h-4 w-4" />{{ t('companyProfile.newSector') }}</button>
                <span v-for="name in state.new_sector_names" :key="name" class="inline-flex items-center gap-1 rounded-full bg-surface px-3 py-1 text-xs ring-1 ring-hairline">
                    {{ name }}
                    <button type="button" :aria-label="t('companyProfile.remove', { name })" @click="removeSector(name)"><PhX class="h-3 w-3" /></button>
                </span>
            </div>
            <p class="mt-2 text-xs text-ink-muted">{{ t('companyProfile.newSectorHelp') }}</p>
            <p v-if="err('sector_ids') || err('new_sector_names')" class="field-error mt-2" role="alert">{{ err('sector_ids') || err('new_sector_names') }}</p>
        </fieldset>
    </div>
</template>
