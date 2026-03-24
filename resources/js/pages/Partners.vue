<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Teleport, computed, ref } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';

type EventSummary = {
    id: number
    // Some places use `title`, others use `name`
    title?: string
    name?: string
    date: string
}

type PartnerSummary = {
    id: number
    name: string
    // Some places use `logo_url`/`website_url`, others use `image_url`/`url`
    logo_url?: string | null
    image_url?: string | null
    website_url?: string | null
    url?: string | null
    description: string | null
    stand_number?: string | number | null
    educations?: { id: number; name: string }[] | string[] | null
}

const props = defineProps<{
    event: EventSummary | null
    partners?: PartnerSummary[]
    supportPartners?: PartnerSummary[]
    standPartners?: PartnerSummary[]
}>();

function formatDate(iso: string) {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;

    const parts = new Intl.DateTimeFormat('nl-NL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).formatToParts(d);

    const day = parts.find((p) => p.type === 'day')?.value ?? '';
    const year = parts.find((p) => p.type === 'year')?.value ?? '';

    // nl-NL short month can include a trailing dot (e.g. "jan.")
    const monthRaw = parts.find((p) => p.type === 'month')?.value ?? '';
    const month = monthRaw.replace('.', '').toLowerCase();

    return `${day}-${month}-${year}`;
}

const eventTitle = () => props.event?.title ?? props.event?.name ?? null;
const partnerLogo = (p: PartnerSummary) => p.logo_url ?? p.image_url ?? null;
const partnerUrl = (p: PartnerSummary) => p.website_url ?? p.url ?? null;
const supportPartners = props.supportPartners ?? props.partners ?? [];
const standPartners = props.standPartners ?? [];

const selectedPartner = ref<PartnerSummary | null>(null);

function openPartner(partner: PartnerSummary) {
    selectedPartner.value = partner;
}

function closePartner() {
    selectedPartner.value = null;
}


const selectedPartnerEducations = computed(() => {
    const educations = selectedPartner.value?.educations;

    if (!educations || !Array.isArray(educations)) return [];

    return educations.map((education) => {
        if (typeof education === 'string') return education;

        return education?.name ?? '';
    }).filter(Boolean);
});
</script>

<template>
    <div>
        <Head title="Partners" />

        <AppHeader />

        <section class="bg-background px-6 py-20 lg:px-16">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-sm font-semibold text-primary">
                      <span v-if="props.event">Editie: {{ eventTitle() }}</span>
                      <span v-else>ATIx Bedrijvendag</span>
                    </p>

                    <h1 class="mt-4 text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">Partners</h1>

                    <div class="mx-auto mt-5 h-1 w-56 rounded-full bg-primary"></div>

                    <p class="mt-6 text-base leading-relaxed text-muted-foreground">
            <span v-if="props.event">
              Dankzij onderstaande partners kunnen wij dit jaar een succesvolle ATIx Bedrijvendag organiseren.
              <span class="block mt-2 text-sm">
                Huidige editie:
                <span class="font-semibold text-foreground">{{ eventTitle() }}</span>
                <span class="mx-2">•</span>
                <span>{{ formatDate(props.event.date) }}</span>
              </span>
            </span>
                        <span v-else>
              Dankzij onderstaande partners kunnen wij dit jaar een succesvolle ATIx Bedrijvendag organiseren.
            </span>
                    </p>
                </div>

                <div v-if="supportPartners.length" class="mt-14">
                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold tracking-tight text-foreground">Partners die dit evenement mogelijk maken</h2>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            Deze partners ondersteunen de ATIx Bedrijvendag en maken de editie mogelijk.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="partner in supportPartners"
                            :key="`support-${partner.id}`"
                            class="group cursor-pointer overflow-hidden rounded-2xl bg-background shadow-sm ring-1 ring-border transition hover:shadow-xl"
                            role="button"
                            tabindex="0"
                            @click="openPartner(partner)"
                            @keydown.enter.prevent="openPartner(partner)"
                            @keydown.space.prevent="openPartner(partner)"
                        >
                            <div class="flex items-center justify-center bg-accent/10 p-10">
                                <img
                                    v-if="partnerLogo(partner)"
                                    :src="partnerLogo(partner) as string"
                                    :alt="partner.name"
                                    class="max-h-20 w-full max-w-[260px] object-contain opacity-90 grayscale transition duration-200 group-hover:opacity-100 group-hover:grayscale-0"
                                    loading="lazy"
                                    decoding="async"
                                />
                                <div v-else class="text-sm text-muted-foreground">Geen logo</div>
                            </div>

                            <div class="p-7">
                                <h2 class="text-xl font-semibold tracking-tight text-foreground">{{ partner.name }}</h2>

                                <div
                                    v-if="partner.description"
                                    class="mt-3 line-clamp-3 text-sm leading-relaxed text-muted-foreground"
                                    v-html="partner.description"
                                ></div>

                                <div class="mt-6">
                                    <a
                                        v-if="partnerUrl(partner)"
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                        :href="partnerUrl(partner) as string"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        @click.stop
                                    >
                                        Bezoek website
                                    </a>

                                    <div
                                        v-else
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-accent px-6 py-3 text-sm font-semibold text-accent-foreground ring-1 ring-border"
                                    >
                                        Website ontbreekt
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <div v-if="standPartners.length" class="mt-14">
                    <div class="mb-6">
                        <h2 class="text-2xl font-semibold tracking-tight text-foreground">Partners met een stand op het evenement</h2>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            Deze partners zijn aanwezig op de ATIx Bedrijvendag met een eigen stand.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="partner in standPartners"
                            :key="`stand-${partner.id}`"
                            class="group cursor-pointer overflow-hidden rounded-2xl bg-background shadow-sm ring-1 ring-border transition hover:shadow-xl"
                            role="button"
                            tabindex="0"
                            @click="openPartner(partner)"
                            @keydown.enter.prevent="openPartner(partner)"
                            @keydown.space.prevent="openPartner(partner)"
                        >
                            <div class="flex items-center justify-center bg-accent/10 p-10">
                                <img
                                    v-if="partnerLogo(partner)"
                                    :src="partnerLogo(partner) as string"
                                    :alt="partner.name"
                                    class="max-h-20 w-full max-w-[260px] object-contain opacity-90 grayscale transition duration-200 group-hover:opacity-100 group-hover:grayscale-0"
                                    loading="lazy"
                                    decoding="async"
                                />
                                <div v-else class="text-sm text-muted-foreground">Geen logo</div>
                            </div>

                            <div class="p-7">
                                <div class="flex items-start justify-between gap-3">
                                    <h2 class="text-xl font-semibold tracking-tight text-foreground">{{ partner.name }}</h2>
                                    <span
                                        v-if="partner.stand_number"
                                        class="shrink-0 rounded-lg bg-accent px-3 py-1 text-xs font-semibold text-accent-foreground ring-1 ring-border"
                                    >
                                        Stand {{ partner.stand_number }}
                                    </span>
                                </div>

                                <div
                                    v-if="partner.description"
                                    class="mt-3 line-clamp-3 text-sm leading-relaxed text-muted-foreground"
                                    v-html="partner.description"
                                ></div>

                                <div class="mt-6">
                                    <a
                                        v-if="partnerUrl(partner)"
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                        :href="partnerUrl(partner) as string"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        @click.stop
                                    >
                                        Bezoek website
                                    </a>

                                    <div
                                        v-else
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-accent px-6 py-3 text-sm font-semibold text-accent-foreground ring-1 ring-border"
                                    >
                                        Website ontbreekt
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <div v-if="!supportPartners.length && !standPartners.length" class="mx-auto mt-10 max-w-3xl rounded-2xl bg-accent/10 p-6 text-center ring-1 ring-border">
                    <p class="text-muted-foreground">Nog geen partners gekoppeld aan deze editie.</p>
                </div>
            </div>

            <Teleport to="body">
                <div
                    v-if="selectedPartner"
                    class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 p-4"
                    @click.self="closePartner"
                >
                    <div class="relative max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-3xl bg-background shadow-2xl ring-1 ring-border">
                        <button
                            type="button"
                            class="absolute right-4 top-4 z-20 inline-flex h-10 w-10 items-center justify-center rounded-full bg-background/90 text-lg font-semibold text-foreground ring-1 ring-border transition hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40"
                            @click="closePartner"
                        >
                            ×
                        </button>

                        <div class="grid max-h-[90vh] grid-cols-1 overflow-y-auto lg:grid-cols-[minmax(0,360px)_minmax(0,1fr)]">
                            <div class="flex min-h-[260px] items-center justify-center bg-accent/10 p-10 lg:min-h-full">
                                <img
                                    v-if="partnerLogo(selectedPartner)"
                                    :src="partnerLogo(selectedPartner) as string"
                                    :alt="selectedPartner.name"
                                    class="max-h-44 w-full max-w-[260px] object-contain"
                                    loading="lazy"
                                    decoding="async"
                                />
                                <div v-else class="text-sm text-muted-foreground">Geen logo</div>
                            </div>

                            <div class="p-8 lg:p-10">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <h2 class="text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                                            {{ selectedPartner.name }}
                                        </h2>

                                        <div v-if="selectedPartner.stand_number" class="mt-3">
                                            <span class="inline-flex items-center rounded-lg bg-accent px-3 py-1 text-xs font-semibold text-accent-foreground ring-1 ring-border">
                                                Stand {{ selectedPartner.stand_number }}
                                            </span>
                                        </div>
                                    </div>

                                    <a
                                        v-if="partnerUrl(selectedPartner)"
                                        class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40"
                                        :href="partnerUrl(selectedPartner) as string"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Bezoek website
                                    </a>
                                </div>

                                <div v-if="selectedPartnerEducations.length" class="mt-6">
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Opleidingen</h3>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span
                                            v-for="education in selectedPartnerEducations"
                                            :key="education"
                                            class="inline-flex items-center rounded-full bg-accent px-3 py-1 text-xs font-medium text-accent-foreground ring-1 ring-border"
                                        >
                                            {{ education }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="selectedPartner.description" class="prose prose-sm mt-8 max-w-none text-foreground">
                                    <div v-html="selectedPartner.description"></div>
                                </div>

                                <div v-else class="mt-8 text-sm text-muted-foreground">
                                    Geen extra beschrijving beschikbaar.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
        </section>
    </div>

    <AppFooter />

</template>

<style scoped>

</style>
