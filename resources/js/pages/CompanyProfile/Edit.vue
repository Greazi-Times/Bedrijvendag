<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    ExternalLink,
    Link2,
    List,
    ListOrdered,
    Pilcrow,
    Plus,
    Quote,
    Redo2,
    RemoveFormatting,
    Search,
    Send,
    SeparatorHorizontal,
    Type,
    Underline,
    Undo2,
    Upload,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref } from 'vue';
import type { Component } from 'vue';

import PageIntro from '@/components/site/PageIntro.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import { useTranslations } from '@/i18n';

type Option = {
    id: number;
    name: string;
};

type ToolbarItem = {
    label: string;
    command: string;
    value?: string;
    text?: string;
    class?: string;
    icon?: Component;
};

const props = defineProps<{
    company: {
        name: string;
        logo_url?: string | null;
        website_url?: string | null;
        description?: string | null;
        education_ids: number[];
        sector_ids: number[];
    };
    options: {
        educations: Option[];
        sectors: Option[];
    };
    pendingSubmission?: {
        submitted_at?: string | null;
    } | null;
    submitUrl: string;
}>();
const { dateLocale, t } = useTranslations();

const logoPreview = ref<string | null>(props.company.logo_url ?? null);
const saved = ref(false);
const editor = ref<HTMLElement | null>(null);
const descriptionMaxLength = 5000;
const allowedEditorTags = new Set(['A', 'B', 'BLOCKQUOTE', 'BR', 'EM', 'H2', 'H3', 'HR', 'I', 'LI', 'OL', 'P', 'S', 'STRONG', 'U', 'UL']);
const sectorSearch = ref('');
const newSectorName = ref('');

const form = useForm({
    contact_name: '',
    contact_email: '',
    name: props.company.name ?? '',
    logo: null as File | null,
    website_url: props.company.website_url ?? '',
    description: props.company.description ?? '',
    education_ids: [...(props.company.education_ids ?? [])],
    sector_ids: [...(props.company.sector_ids ?? [])],
    new_sector_names: [] as string[],
});

const submittedAt = computed(() => {
    if (!props.pendingSubmission?.submitted_at) return null;

    const date = new Date(props.pendingSubmission.submitted_at);
    if (Number.isNaN(date.getTime())) return null;

    return date.toLocaleDateString(dateLocale.value, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
});

const toolbarGroups = computed<ToolbarItem[][]>(() => [
    [
        { label: t('companyProfile.bold'), command: 'bold', text: 'B', class: 'font-bold' },
        { label: t('companyProfile.italic'), command: 'italic', text: 'I', class: 'font-serif italic' },
        { label: t('companyProfile.underline'), command: 'underline', icon: Underline },
        { label: t('companyProfile.strike'), command: 'strikeThrough', text: 'S', class: 'line-through' },
    ],
    [
        { label: t('companyProfile.heading'), command: 'formatBlock', value: 'h2', icon: Type },
        { label: t('companyProfile.subheading'), command: 'formatBlock', value: 'h3', icon: Pilcrow },
        { label: t('companyProfile.quote'), command: 'formatBlock', value: 'blockquote', icon: Quote },
    ],
    [
        { label: t('companyProfile.bullets'), command: 'insertUnorderedList', icon: List },
        { label: t('companyProfile.numbered'), command: 'insertOrderedList', icon: ListOrdered },
        { label: t('companyProfile.line'), command: 'insertHorizontalRule', icon: SeparatorHorizontal },
    ],
    [
        { label: t('companyProfile.insertLink'), command: 'createLink', icon: Link2 },
        { label: t('companyProfile.clearFormatting'), command: 'removeFormat', icon: RemoveFormatting },
        { label: t('companyProfile.undo'), command: 'undo', icon: Undo2 },
        { label: t('companyProfile.redo'), command: 'redo', icon: Redo2 },
    ],
]);

const filteredSectors = computed(() => {
    const query = sectorSearch.value.trim().toLowerCase();

    if (!query) return props.options.sectors;

    return props.options.sectors.filter((sector) => sector.name.toLowerCase().includes(query));
});

const canAddNewSector = computed(() => {
    const name = normalizedSectorName(newSectorName.value || sectorSearch.value);

    if (!name) return false;

    const key = name.toLowerCase();
    const alreadyExists = props.options.sectors.some((sector) => sector.name.trim().toLowerCase() === key);
    const alreadyProposed = form.new_sector_names.some((sectorName) => sectorName.trim().toLowerCase() === key);

    return !alreadyExists && !alreadyProposed && form.new_sector_names.length < 10;
});

const descriptionLength = computed(() => {
    const text = form.description.replace(/<[^>]*>/g, '').replace(/&(#\d+|#x[\da-f]+|[a-z\d]+);/gi, ' ');

    return [...text.trim()].length;
});

const sectorError = computed(() => {
    return form.errors.sector_ids || form.errors.new_sector_names || Object.entries(form.errors).find(([key]) => key.startsWith('new_sector_names.'))?.[1];
});

onMounted(() => {
    if (editor.value) {
        editor.value.innerHTML = form.description;
    }
});

function toggleValue(values: number[], id: number) {
    const index = values.indexOf(id);
    if (index >= 0) values.splice(index, 1);
    else values.push(id);
}

function normalizedSectorName(value: string) {
    return value.replace(/\s+/g, ' ').trim();
}

function addNewSector() {
    const name = normalizedSectorName(newSectorName.value || sectorSearch.value);

    if (!canAddNewSector.value) return;

    form.new_sector_names.push(name);
    newSectorName.value = '';
    sectorSearch.value = '';
}

function removeNewSector(name: string) {
    form.new_sector_names = form.new_sector_names.filter((sectorName) => sectorName !== name);
}

function syncDescription() {
    form.description = editor.value?.innerHTML ?? '';
}

function focusEditor() {
    editor.value?.focus();
}

function runEditorCommand(command: string, value?: string) {
    focusEditor();

    if (command === 'createLink') {
        const url = window.prompt(t('companyProfile.linkPrompt'));
        if (!url) return;

        document.execCommand('createLink', false, url);
    } else {
        document.execCommand(command, false, value);
    }

    syncDescription();
}

function handleEditorInput() {
    syncDescription();
}

function cleanPastedHtml(html: string) {
    const source = new DOMParser().parseFromString(html, 'text/html');
    const output = document.createElement('div');

    const appendCleaned = (node: Node, parent: Node) => {
        if (node.nodeType === Node.TEXT_NODE) {
            parent.appendChild(document.createTextNode(node.textContent ?? ''));
            return;
        }

        if (!(node instanceof Element) || ['SCRIPT', 'STYLE', 'META', 'LINK', 'TITLE'].includes(node.tagName)) return;

        let target: Node = parent;

        if (allowedEditorTags.has(node.tagName)) {
            const element = document.createElement(node.tagName.toLowerCase());
            const href = node.getAttribute('href');

            if (node.tagName === 'A' && href && /^(https?:\/\/|mailto:)/i.test(href)) {
                element.setAttribute('href', href);
            }

            parent.appendChild(element);
            target = element;
        } else if (['DIV', 'H1', 'H4', 'H5', 'H6'].includes(node.tagName)) {
            const element = document.createElement('p');
            parent.appendChild(element);
            target = element;
        }

        node.childNodes.forEach((child) => appendCleaned(child, target));
    };

    source.body.childNodes.forEach((child) => appendCleaned(child, output));

    return output.innerHTML;
}

function handleEditorPaste(event: ClipboardEvent) {
    event.preventDefault();

    const html = event.clipboardData?.getData('text/html');
    const text = event.clipboardData?.getData('text/plain') ?? '';
    const escapeHtml = (value: string) => value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    const fallbackHtml = text
        .split(/\n{2,}/)
        .map((paragraph) => paragraph.trim())
        .filter(Boolean)
        .map((paragraph) => `<p>${escapeHtml(paragraph).replace(/\n/g, '<br>')}</p>`)
        .join('');

    document.execCommand('insertHTML', false, html ? cleanPastedHtml(html) : fallbackHtml);

    nextTick(syncDescription);
}

function handleLogoChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    form.logo = file;

    if (!file) {
        logoPreview.value = props.company.logo_url ?? null;
        return;
    }

    logoPreview.value = URL.createObjectURL(file);
}

function submit() {
    syncDescription();

    form.post(props.submitUrl, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            saved.value = true;
            form.logo = null;
        },
    });
}
</script>

<template>
    <Head :title="t('companyProfile.head')" />

    <SiteLayout>
        <PageIntro :eyebrow="t('companyProfile.eyebrow')" :title="t('companyProfile.title')" :lead="t('companyProfile.intro')" />

        <section class="site-container pt-14 pb-24 md:pt-20 md:pb-32">
            <div class="mx-auto max-w-4xl">
                <div v-if="pendingSubmission || saved" class="alert alert-success mt-0 mb-8" role="status" aria-live="polite">
                    <div class="flex items-start gap-3">
                        <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-success" />
                        <p>
                            {{ t('companyProfile.received', { date: submittedAt ? t('companyProfile.onDate', { date: submittedAt }) : '' }) }}
                        </p>
                    </div>
                </div>

                <form class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]" @submit.prevent="submit">
                    <section class="card rounded-[var(--radius-card)] p-6 sm:p-8">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="contact_name" class="field-label mb-2 block"
                                    >{{ t('companyProfile.contactPerson') }} <span class="text-danger" aria-hidden="true">*</span></label
                                >
                                <input id="contact_name" v-model="form.contact_name" type="text" autocomplete="name" required class="input" />
                                <p v-if="form.errors.contact_name" class="field-error mt-2">{{ form.errors.contact_name }}</p>
                            </div>

                            <div>
                                <label for="contact_email" class="field-label mb-2 block"
                                    >{{ t('companyProfile.contactEmail') }} <span class="text-danger" aria-hidden="true">*</span></label
                                >
                                <input id="contact_email" v-model="form.contact_email" type="email" autocomplete="email" required class="input" />
                                <p v-if="form.errors.contact_email" class="field-error mt-2">{{ form.errors.contact_email }}</p>
                            </div>

                            <div>
                                <label for="name" class="field-label mb-2 block">{{ t('companyProfile.companyName') }} <span class="text-danger" aria-hidden="true">*</span></label>
                                <input id="name" v-model="form.name" type="text" required class="input" />
                                <p v-if="form.errors.name" class="field-error mt-2">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label for="website_url" class="field-label mb-2 block">{{ t('common.website') }}</label>
                                <input id="website_url" v-model="form.website_url" type="url" placeholder="https://example.com" class="input" />
                                <p v-if="form.errors.website_url" class="field-error mt-2">{{ form.errors.website_url }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="description" class="field-label mb-2 block">{{ t('common.description') }}</label>
                                <div class="overflow-hidden rounded-[var(--radius-input)] ring-1 ring-hairline focus-within:ring-2 focus-within:ring-ring/40">
                                    <div class="flex flex-wrap gap-1 border-b border-hairline bg-canvas p-2">
                                        <template v-for="(group, groupIndex) in toolbarGroups" :key="groupIndex">
                                            <span v-if="groupIndex > 0" class="mx-1 h-8 w-px bg-border" aria-hidden="true"></span>
                                            <button
                                                v-for="item in group"
                                                :key="item.label"
                                                type="button"
                                                :title="item.label"
                                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm font-semibold text-ink transition hover:bg-secondary/15 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                                @click="runEditorCommand(item.command, item.value)"
                                            >
                                                <component :is="item.icon" v-if="item.icon" class="h-4 w-4" />
                                                <span v-else :class="item.class">{{ item.text }}</span>
                                            </button>
                                        </template>
                                    </div>

                                    <div
                                        id="description"
                                        ref="editor"
                                        contenteditable="true"
                                        class="rich-editor prose prose-sm min-h-56 max-w-none bg-canvas p-4 text-ink outline-none dark:prose-invert"
                                        role="textbox"
                                        aria-multiline="true"
                                        :data-placeholder="t('companyProfile.descriptionPlaceholder')"
                                        @input="handleEditorInput"
                                        @paste="handleEditorPaste"
                                        @blur="syncDescription"
                                    ></div>
                                </div>
                                <textarea v-model="form.description" name="description" class="sr-only" tabindex="-1" aria-hidden="true"></textarea>
                                <div class="mt-2 flex items-start justify-between gap-4 text-xs text-ink-muted">
                                    <p>{{ t('companyProfile.editorHelp') }}</p>
                                    <p class="shrink-0 tabular-nums" :class="{ 'text-danger': descriptionLength > descriptionMaxLength }">
                                        {{ descriptionLength }} / {{ descriptionMaxLength }}
                                    </p>
                                </div>
                                <p v-if="form.errors.description" class="field-error mt-2">{{ form.errors.description }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <p class="text-sm font-semibold text-ink">{{ t('common.sectors') }}</p>
                                <div class="relative mt-4">
                                    <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-ink-muted" />
                                    <input v-model="sectorSearch" type="search" :placeholder="t('companyProfile.searchSector')" class="input pl-11" />
                                </div>

                                <div class="mt-4 max-h-64 overflow-y-auto rounded-[var(--radius-input)] bg-surface p-3 ring-1 ring-hairline">
                                    <div class="flex flex-wrap gap-3">
                                        <label
                                            v-for="sector in filteredSectors"
                                            :key="sector.id"
                                            class="inline-flex max-w-full items-center gap-3 rounded-lg bg-canvas px-3 py-2 text-sm whitespace-nowrap text-ink ring-1 ring-hairline"
                                        >
                                            <input
                                                type="checkbox"
                                                class="h-4 w-4 shrink-0 rounded border-hairline text-brand-ink focus:ring-ring/40"
                                                :checked="form.sector_ids.includes(sector.id)"
                                                @change="toggleValue(form.sector_ids, sector.id)"
                                            />
                                            <span>{{ sector.name }}</span>
                                        </label>
                                    </div>

                                    <p v-if="!filteredSectors.length" class="text-sm text-ink-muted">{{ t('companyProfile.noSector') }}</p>
                                </div>

                                <div class="mt-4 rounded-[var(--radius-input)] bg-surface p-4 ring-1 ring-hairline">
                                    <label for="new_sector_name" class="text-sm font-semibold text-ink">{{ t('companyProfile.newSector') }}</label>
                                    <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                                        <input
                                            id="new_sector_name"
                                            v-model="newSectorName"
                                            type="text"
                                            maxlength="80"
                                            :placeholder="t('companyProfile.newSectorExample')"
                                            class="input min-w-0 flex-1"
                                            @keydown.enter.prevent="addNewSector"
                                        />
                                        <button type="button" :disabled="!canAddNewSector" class="btn btn-primary" @click="addNewSector">
                                            <Plus class="h-4 w-4" />
                                            {{ t('companyProfile.add') }}
                                        </button>
                                    </div>
                                    <p class="mt-2 text-xs text-ink-muted">{{ t('companyProfile.newSectorHelp') }}</p>

                                    <div v-if="form.new_sector_names.length" class="mt-4 flex flex-wrap gap-2">
                                        <span
                                            v-for="name in form.new_sector_names"
                                            :key="name"
                                            class="inline-flex max-w-full items-center gap-2 rounded-lg bg-primary/10 px-3 py-2 text-sm font-semibold whitespace-nowrap text-brand-ink ring-1 ring-brand/25"
                                        >
                                            {{ name }}
                                            <button
                                                type="button"
                                                :aria-label="t('companyProfile.remove', { name })"
                                                class="rounded-md p-0.5 transition hover:bg-primary/10"
                                                @click="removeNewSector(name)"
                                            >
                                                <X class="h-3.5 w-3.5" />
                                            </button>
                                        </span>
                                    </div>
                                </div>

                                <p v-if="sectorError" class="field-error mt-2">{{ sectorError }}</p>
                            </div>
                        </div>
                    </section>

                    <aside class="space-y-8">
                        <section class="card rounded-[var(--radius-card)] p-6">
                            <p class="text-sm font-semibold text-ink">{{ t('companyProfile.logo') }}</p>
                            <div class="mt-4 flex aspect-[4/3] items-center justify-center rounded-[var(--radius-input)] bg-canvas p-6 ring-1 ring-hairline">
                                <img v-if="logoPreview" :src="logoPreview" :alt="form.name" class="max-h-full max-w-full object-contain" />
                                <span v-else class="text-sm text-ink-muted">{{ t('common.noLogo') }}</span>
                            </div>
                            <label
                                for="logo"
                                class="mt-4 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-[var(--radius-input)] bg-canvas px-4 py-3 text-sm font-semibold text-ink ring-1 ring-hairline transition hover:bg-surface"
                            >
                                <Upload class="h-4 w-4" />
                                {{ t('companyProfile.uploadLogo') }}
                            </label>
                            <input id="logo" type="file" accept="image/*" class="sr-only" @change="handleLogoChange" />
                            <p v-if="form.errors.logo" class="field-error mt-2">{{ form.errors.logo }}</p>
                        </section>

                        <section class="card rounded-[var(--radius-card)] p-6">
                            <p class="text-sm font-semibold text-ink">{{ t('common.educations') }}</p>
                            <div class="mt-4 space-y-3">
                                <label v-for="education in options.educations" :key="education.id" class="flex items-start gap-3 text-sm text-ink">
                                    <input
                                        type="checkbox"
                                        class="mt-1 h-4 w-4 rounded border-hairline text-brand-ink focus:ring-ring/40"
                                        :checked="form.education_ids.includes(education.id)"
                                        @change="toggleValue(form.education_ids, education.id)"
                                    />
                                    <span>{{ education.name }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.education_ids" class="field-error mt-2">{{ form.errors.education_ids }}</p>
                        </section>
                    </aside>

                    <div class="card flex flex-col gap-4 rounded-[var(--radius-card)] p-6 sm:flex-row sm:items-center sm:justify-between lg:col-span-2">
                        <p class="text-sm leading-relaxed text-ink-muted">{{ t('companyProfile.approval') }}</p>
                        <button type="submit" :disabled="form.processing" class="btn btn-primary">
                            <Send class="h-4 w-4" />
                            {{ form.processing ? t('common.submitting') : t('companyProfile.sendReview') }}
                        </button>
                    </div>
                </form>

                <a
                    v-if="company.website_url"
                    :href="company.website_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-brand-ink hover:text-brand-ink/80"
                >
                    {{ t('companyProfile.openWebsite') }}
                    <ExternalLink class="h-4 w-4" />
                </a>
            </div>
        </section>
    </SiteLayout>
</template>

<style scoped>
.rich-editor:empty::before {
    content: attr(data-placeholder);
    color: var(--muted-foreground);
}

.rich-editor :deep(a) {
    color: var(--primary);
    text-decoration: underline;
}
</style>
