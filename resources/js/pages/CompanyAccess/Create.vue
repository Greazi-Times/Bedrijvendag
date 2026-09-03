<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Building2, CheckCircle2, PlusCircle, Search, Send } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';

type CompanyOption = {
    id: number;
    name: string;
    website_url?: string | null;
};

const props = defineProps<{
    companies: CompanyOption[];
    submitUrl: string;
}>();

const mode = ref<'existing' | 'new'>('existing');
const query = ref('');
const saved = ref(false);

const form = useForm({
    type: 'existing',
    company_id: null as number | null,
    company_name: '',
    website_url: '',
    contact_name: '',
    contact_email: '',
    message: '',
});

const filteredCompanies = computed(() => {
    const value = query.value.trim().toLowerCase();

    if (!value) return props.companies.slice(0, 12);

    return props.companies
        .filter((company) => [company.name, company.website_url ?? ''].join(' ').toLowerCase().includes(value))
        .slice(0, 20);
});

const selectedCompany = computed(() => props.companies.find((company) => company.id === form.company_id) ?? null);

watch(mode, (value) => {
    form.type = value;
    saved.value = false;

    if (value === 'existing') {
        form.company_name = '';
        form.website_url = '';
    } else {
        form.company_id = null;
        query.value = '';
    }
});

function selectCompany(company: CompanyOption) {
    form.company_id = company.id;
    query.value = company.name;
}

function submit() {
    form.type = mode.value;

    form.post(props.submitUrl, {
        preserveScroll: true,
        onSuccess: () => {
            saved.value = true;
            form.reset('company_id', 'company_name', 'website_url', 'contact_name', 'contact_email', 'message');
            query.value = '';
        },
    });
}
</script>

<template>
    <Head title="Bedrijfstoegang aanvragen" />

    <AppHeader class="sticky top-0 z-50" />

    <main class="brand-hero min-h-screen px-6 py-12 lg:px-16">
        <div class="mx-auto max-w-5xl">
            <div class="max-w-3xl">
                <p class="brand-eyebrow">Bedrijfstoegang</p>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">Vraag toegang tot jullie bedrijfsprofiel aan</h1>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">
                    Zoek eerst of jullie bedrijf al in de lijst staat. De organisatie controleert elke aanvraag voordat er een persoonlijke bewerklink wordt gedeeld.
                </p>
            </div>

            <div
                v-if="saved"
                class="mt-8 rounded-xl bg-emerald-500/15 p-4 text-sm text-emerald-900 ring-1 ring-emerald-500/25"
                role="status"
                aria-live="polite"
            >
                <div class="flex items-start gap-3">
                    <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                    <p>Je aanvraag is ontvangen. De organisatie controleert deze voordat er toegang wordt gegeven.</p>
                </div>
            </div>

            <form class="brand-card mt-10 rounded-2xl p-6 sm:p-8" @submit.prevent="submit">
                <div class="grid gap-3 sm:grid-cols-2">
                    <button
                        type="button"
                        class="flex items-center gap-3 rounded-xl px-4 py-3 text-left text-sm font-semibold ring-1 transition"
                        :class="mode === 'existing' ? 'bg-primary text-primary-foreground ring-primary' : 'bg-background text-foreground ring-border hover:bg-secondary/10'"
                        @click="mode = 'existing'"
                    >
                        <Building2 class="h-5 w-5 shrink-0" />
                        Mijn bedrijf staat in de lijst
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-3 rounded-xl px-4 py-3 text-left text-sm font-semibold ring-1 transition"
                        :class="mode === 'new' ? 'bg-primary text-primary-foreground ring-primary' : 'bg-background text-foreground ring-border hover:bg-secondary/10'"
                        @click="mode = 'new'"
                    >
                        <PlusCircle class="h-5 w-5 shrink-0" />
                        Mijn bedrijf staat er nog niet bij
                    </button>
                </div>

                <div class="mt-8 grid gap-6 lg:grid-cols-2">
                    <template v-if="mode === 'existing'">
                        <div class="lg:col-span-2">
                            <label for="company_search" class="mb-2 block text-sm font-semibold text-foreground">Zoek bedrijf</label>
                            <div class="relative">
                                <Search class="pointer-events-none absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <input
                                    id="company_search"
                                    v-model="query"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="Typ de bedrijfsnaam..."
                                    class="brand-input w-full rounded-xl py-3 pr-4 pl-11 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                                />
                            </div>

                            <div class="mt-3 max-h-72 overflow-y-auto rounded-xl bg-background/70 ring-1 ring-border">
                                <button
                                    v-for="company in filteredCompanies"
                                    :key="company.id"
                                    type="button"
                                    class="block w-full border-b border-border px-4 py-3 text-left text-sm transition last:border-b-0 hover:bg-secondary/10"
                                    :class="form.company_id === company.id ? 'bg-primary/10 text-primary' : 'text-foreground'"
                                    @click="selectCompany(company)"
                                >
                                    <span class="font-semibold">{{ company.name }}</span>
                                    <span v-if="company.website_url" class="mt-1 block text-xs text-muted-foreground">{{ company.website_url }}</span>
                                </button>
                                <p v-if="!filteredCompanies.length" class="px-4 py-5 text-sm text-muted-foreground">Geen bedrijf gevonden. Gebruik de optie om een nieuw bedrijf aan te vragen.</p>
                            </div>

                            <p v-if="selectedCompany" class="mt-2 text-xs text-muted-foreground">Geselecteerd: {{ selectedCompany.name }}</p>
                            <p v-if="form.errors.company_id" class="mt-2 text-sm text-destructive">{{ form.errors.company_id }}</p>
                        </div>
                    </template>

                    <template v-else>
                        <div>
                            <label for="company_name" class="mb-2 block text-sm font-semibold text-foreground">Bedrijfsnaam <span class="text-destructive">*</span></label>
                            <input
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />
                            <p v-if="form.errors.company_name" class="mt-2 text-sm text-destructive">{{ form.errors.company_name }}</p>
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
                    </template>

                    <div>
                        <label for="contact_name" class="mb-2 block text-sm font-semibold text-foreground">Contactpersoon <span class="text-destructive">*</span></label>
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
                        <label for="contact_email" class="mb-2 block text-sm font-semibold text-foreground">Zakelijk e-mailadres <span class="text-destructive">*</span></label>
                        <input
                            id="contact_email"
                            v-model="form.contact_email"
                            type="email"
                            autocomplete="email"
                            class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                        />
                        <p v-if="form.errors.contact_email" class="mt-2 text-sm text-destructive">{{ form.errors.contact_email }}</p>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="message" class="mb-2 block text-sm font-semibold text-foreground">Bericht</label>
                        <textarea
                            id="message"
                            v-model="form.message"
                            rows="4"
                            class="brand-input w-full rounded-xl p-4 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                        ></textarea>
                        <p v-if="form.errors.message" class="mt-2 text-sm text-destructive">{{ form.errors.message }}</p>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm leading-relaxed text-muted-foreground">De persoonlijke bewerklink wordt nooit openbaar getoond.</p>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <Send class="h-4 w-4" />
                        {{ form.processing ? 'Versturen...' : 'Aanvraag versturen' }}
                    </button>
                </div>
            </form>
        </div>
    </main>

    <AppFooter />
</template>
