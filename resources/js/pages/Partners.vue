<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
}

const props = defineProps<{
    event: EventSummary | null
    partners: PartnerSummary[]
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

                <div class="mt-14 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="partner in props.partners"
                        :key="partner.id"
                        class="group overflow-hidden rounded-2xl bg-background shadow-sm ring-1 ring-border transition hover:shadow-xl"
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

                            <p v-if="partner.description" class="mt-3 line-clamp-3 text-sm leading-relaxed text-muted-foreground">
                                {{ partner.description }}
                            </p>

                            <div class="mt-6">
                                <a
                                    v-if="partnerUrl(partner)"
                                    class="inline-flex w-full items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                    :href="partnerUrl(partner) as string"
                                    target="_blank"
                                    rel="noopener noreferrer"
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

                <div v-if="!props.partners.length" class="mx-auto mt-10 max-w-3xl rounded-2xl bg-accent/10 p-6 text-center ring-1 ring-border">
                    <p class="text-muted-foreground">Nog geen partners gekoppeld aan deze editie.</p>
                </div>
            </div>
        </section>
    </div>

    <AppFooter />

</template>

<style scoped>

</style>
