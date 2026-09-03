<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, ExternalLink, Link2, List, ListOrdered, Pilcrow, Quote, Redo2, RemoveFormatting, Send, SeparatorHorizontal, Type, Underline, Undo2, Upload } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref } from 'vue';

import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';

type Option = {
    id: number;
    name: string;
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

const logoPreview = ref<string | null>(props.company.logo_url ?? null);
const saved = ref(false);
const editor = ref<HTMLElement | null>(null);

const form = useForm({
    contact_name: '',
    contact_email: '',
    name: props.company.name ?? '',
    logo: null as File | null,
    website_url: props.company.website_url ?? '',
    description: props.company.description ?? '',
    education_ids: [...(props.company.education_ids ?? [])],
    sector_ids: [...(props.company.sector_ids ?? [])],
});

const submittedAt = computed(() => {
    if (!props.pendingSubmission?.submitted_at) return null;

    const date = new Date(props.pendingSubmission.submitted_at);
    if (Number.isNaN(date.getTime())) return null;

    return date.toLocaleDateString('nl-NL', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
});

const toolbarGroups = [
    [
        { label: 'Vet', command: 'bold', text: 'B', class: 'font-bold' },
        { label: 'Cursief', command: 'italic', text: 'I', class: 'font-serif italic' },
        { label: 'Onderstrepen', command: 'underline', icon: Underline },
        { label: 'Doorhalen', command: 'strikeThrough', text: 'S', class: 'line-through' },
    ],
    [
        { label: 'Kop', command: 'formatBlock', value: 'h2', icon: Type },
        { label: 'Subkop', command: 'formatBlock', value: 'h3', icon: Pilcrow },
        { label: 'Citaat', command: 'formatBlock', value: 'blockquote', icon: Quote },
    ],
    [
        { label: 'Opsomming', command: 'insertUnorderedList', icon: List },
        { label: 'Genummerde lijst', command: 'insertOrderedList', icon: ListOrdered },
        { label: 'Horizontale lijn', command: 'insertHorizontalRule', icon: SeparatorHorizontal },
    ],
    [
        { label: 'Link invoegen', command: 'createLink', icon: Link2 },
        { label: 'Opmaak wissen', command: 'removeFormat', icon: RemoveFormatting },
        { label: 'Ongedaan maken', command: 'undo', icon: Undo2 },
        { label: 'Opnieuw', command: 'redo', icon: Redo2 },
    ],
];

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

function syncDescription() {
    form.description = editor.value?.innerHTML ?? '';
}

function focusEditor() {
    editor.value?.focus();
}

function runEditorCommand(command: string, value?: string) {
    focusEditor();

    if (command === 'createLink') {
        const url = window.prompt('Plak de link');
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

function handleEditorPaste(event: ClipboardEvent) {
    event.preventDefault();

    const html = event.clipboardData?.getData('text/html');
    const text = event.clipboardData?.getData('text/plain') ?? '';
    const escapeHtml = (value: string) =>
        value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    const fallbackHtml = text
        .split(/\n{2,}/)
        .map((paragraph) => paragraph.trim())
        .filter(Boolean)
        .map((paragraph) => `<p>${escapeHtml(paragraph).replace(/\n/g, '<br>')}</p>`)
        .join('');

    document.execCommand('insertHTML', false, html || fallbackHtml);

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
    <Head title="Bedrijfsinformatie controleren" />

    <AppHeader class="sticky top-0 z-50" />

    <main class="brand-hero min-h-screen px-6 py-12 lg:px-16">
        <div class="mx-auto max-w-5xl">
            <div class="max-w-3xl">
                <p class="brand-eyebrow">Bedrijfsprofiel</p>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">Controleer jullie bedrijfsinformatie</h1>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">
                    Pas de gegevens aan die op de Bedrijvendag website mogen verschijnen. Na versturen controleert de organisatie de wijzigingen voordat ze live gaan.
                </p>
            </div>

            <div
                v-if="pendingSubmission || saved"
                class="mt-8 rounded-xl bg-emerald-500/15 p-4 text-sm text-emerald-900 ring-1 ring-emerald-500/25"
                role="status"
                aria-live="polite"
            >
                <div class="flex items-start gap-3">
                    <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                    <p>
                        Jullie wijzigingen zijn ontvangen<span v-if="submittedAt"> op {{ submittedAt }}</span
                        >. Je mag het formulier opnieuw versturen als je nog iets wilt aanpassen.
                    </p>
                </div>
            </div>

            <form class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]" @submit.prevent="submit">
                <section class="brand-card rounded-2xl p-6 sm:p-8">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label for="contact_name" class="mb-2 block text-sm font-semibold text-foreground">Contactpersoon</label>
                            <input
                                id="contact_name"
                                v-model="form.contact_name"
                                type="text"
                                autocomplete="name"
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />
                            <p v-if="form.errors.contact_name" class="mt-2 text-sm text-destructive">{{ form.errors.contact_name }}</p>
                        </div>

                        <div>
                            <label for="contact_email" class="mb-2 block text-sm font-semibold text-foreground">Contact e-mail</label>
                            <input
                                id="contact_email"
                                v-model="form.contact_email"
                                type="email"
                                autocomplete="email"
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />
                            <p v-if="form.errors.contact_email" class="mt-2 text-sm text-destructive">{{ form.errors.contact_email }}</p>
                        </div>

                        <div>
                            <label for="name" class="mb-2 block text-sm font-semibold text-foreground">Bedrijfsnaam <span class="text-destructive">*</span></label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />
                            <p v-if="form.errors.name" class="mt-2 text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label for="website_url" class="mb-2 block text-sm font-semibold text-foreground">Website</label>
                            <input
                                id="website_url"
                                v-model="form.website_url"
                                type="url"
                                placeholder="https://example.com"
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />
                            <p v-if="form.errors.website_url" class="mt-2 text-sm text-destructive">{{ form.errors.website_url }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="description" class="mb-2 block text-sm font-semibold text-foreground">Beschrijving</label>
                            <div class="overflow-hidden rounded-xl ring-1 ring-border focus-within:ring-2 focus-within:ring-ring/40">
                                <div class="flex flex-wrap gap-1 border-b border-border bg-background/80 p-2">
                                    <template v-for="(group, groupIndex) in toolbarGroups" :key="groupIndex">
                                        <span v-if="groupIndex > 0" class="mx-1 h-8 w-px bg-border" aria-hidden="true"></span>
                                        <button
                                            v-for="item in group"
                                            :key="item.label"
                                            type="button"
                                            :title="item.label"
                                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm font-semibold text-foreground transition hover:bg-secondary/15 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
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
                                    class="rich-editor prose prose-sm min-h-56 max-w-none bg-background p-4 text-foreground outline-none dark:prose-invert"
                                    role="textbox"
                                    aria-multiline="true"
                                    data-placeholder="Schrijf een korte bedrijfsomschrijving..."
                                    @input="handleEditorInput"
                                    @paste="handleEditorPaste"
                                    @blur="syncDescription"
                                ></div>
                            </div>
                            <textarea
                                v-model="form.description"
                                name="description"
                                class="sr-only"
                                tabindex="-1"
                                aria-hidden="true"
                            ></textarea>
                            <p class="mt-2 text-xs text-muted-foreground">Gebruik de knoppen voor koppen, lijsten, vet, cursief, onderstreept, links en scheidingslijnen.</p>
                            <p v-if="form.errors.description" class="mt-2 text-sm text-destructive">{{ form.errors.description }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <p class="text-sm font-semibold text-foreground">Sectoren</p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <label v-for="sector in options.sectors" :key="sector.id" class="inline-flex max-w-full items-center gap-3 rounded-lg bg-background/60 px-3 py-2 text-sm whitespace-nowrap text-foreground ring-1 ring-border">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 shrink-0 rounded border-border text-primary focus:ring-ring/40"
                                        :checked="form.sector_ids.includes(sector.id)"
                                        @change="toggleValue(form.sector_ids, sector.id)"
                                    />
                                    <span>{{ sector.name }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.sector_ids" class="mt-2 text-sm text-destructive">{{ form.errors.sector_ids }}</p>
                        </div>
                    </div>
                </section>

                <aside class="space-y-8">
                    <section class="brand-card rounded-2xl p-6">
                        <p class="text-sm font-semibold text-foreground">Logo</p>
                        <div class="mt-4 flex aspect-[4/3] items-center justify-center rounded-xl bg-background/70 p-6 ring-1 ring-border">
                            <img v-if="logoPreview" :src="logoPreview" :alt="form.name" class="max-h-full max-w-full object-contain" />
                            <span v-else class="text-sm text-muted-foreground">Geen logo</span>
                        </div>
                        <label
                            for="logo"
                            class="mt-4 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-background px-4 py-3 text-sm font-semibold text-foreground ring-1 ring-border transition hover:bg-secondary/10"
                        >
                            <Upload class="h-4 w-4" />
                            Upload logo
                        </label>
                        <input id="logo" type="file" accept="image/*" class="sr-only" @change="handleLogoChange" />
                        <p v-if="form.errors.logo" class="mt-2 text-sm text-destructive">{{ form.errors.logo }}</p>
                    </section>

                    <section class="brand-card rounded-2xl p-6">
                        <p class="text-sm font-semibold text-foreground">Opleidingen</p>
                        <div class="mt-4 space-y-3">
                            <label v-for="education in options.educations" :key="education.id" class="flex items-start gap-3 text-sm text-foreground">
                                <input
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-border text-primary focus:ring-ring/40"
                                    :checked="form.education_ids.includes(education.id)"
                                    @change="toggleValue(form.education_ids, education.id)"
                                />
                                <span>{{ education.name }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.education_ids" class="mt-2 text-sm text-destructive">{{ form.errors.education_ids }}</p>
                    </section>

                </aside>

                <div class="brand-card flex flex-col gap-4 rounded-2xl p-6 sm:flex-row sm:items-center sm:justify-between lg:col-span-2">
                    <p class="text-sm leading-relaxed text-muted-foreground">Na goedkeuring vervangen deze gegevens het huidige bedrijfsprofiel op de website.</p>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <Send class="h-4 w-4" />
                        {{ form.processing ? 'Versturen...' : 'Verstuur ter controle' }}
                    </button>
                </div>
            </form>

            <a
                v-if="company.website_url"
                :href="company.website_url"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-primary hover:text-primary/80"
            >
                Huidige website openen
                <ExternalLink class="h-4 w-4" />
            </a>
        </div>
    </main>

    <AppFooter />
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
