<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { PhArrowUpRight, PhHandshake, PhPlus } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import CompanyDialog from '@/components/site/CompanyDialog.vue';
import EmptyState from '@/components/site/EmptyState.vue';
import PageIntro from '@/components/site/PageIntro.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import { useTranslations } from '@/i18n';
import { htmlToText } from '@/lib/sanitize';
import type { CompanyDialogData } from '@/types/site';

type EventSummary = {
    id: number;
    // Some places use `title`, others use `name`
    title?: string;
    name?: string;
    date: string;
};

type PartnerSummary = {
    id: number;
    name: string;
    logo_url?: string | null;
    website_url?: string | null;
    url?: string | null;
    description: string | null;
    stand_number?: string | number | null;
    educations?: { id: number; name: string }[] | string[] | null;
};

const props = defineProps<{
    event: EventSummary | null;
    partners?: PartnerSummary[];
    supportPartners?: PartnerSummary[];
    standPartners?: PartnerSummary[];
}>();
const { dateLocale, t } = useTranslations();

function formatDate(iso: string) {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;

    const parts = new Intl.DateTimeFormat(dateLocale.value, {
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
const partnerLogo = (p: PartnerSummary) => p.logo_url ?? null;
const partnerUrl = (p: PartnerSummary) => p.website_url ?? p.url ?? null;
const supportPartners = props.supportPartners ?? props.partners ?? [];
const standPartners = props.standPartners ?? [];

const selectedPartner = ref<PartnerSummary | null>(null);
const isPartnerOpen = ref(false);

function openPartner(partner: PartnerSummary) {
    selectedPartner.value = partner;
    isPartnerOpen.value = true;
}

const sections = computed(() =>
    [
        { key: 'support', title: t('partners.supportingTitle'), description: t('partners.supportingDescription'), partners: supportPartners, featured: true },
        { key: 'stand', title: t('partners.standsTitle'), description: t('partners.standsDescription'), partners: standPartners, featured: false },
    ].filter((section) => section.partners.length),
);

const selectedPartnerEducations = computed(() => {
    const educations = selectedPartner.value?.educations;

    if (!educations || !Array.isArray(educations)) return [];

    return educations
        .map((education) => {
            if (typeof education === 'string') return education;

            return education?.name ?? '';
        })
        .filter(Boolean);
});

const partnerDialogData = computed<CompanyDialogData | null>(() => {
    const p = selectedPartner.value;
    if (!p) return null;

    return {
        name: p.name,
        kind: t('map.partner'),
        stand: p.stand_number !== null && p.stand_number !== undefined ? String(p.stand_number) : null,
        logoUrl: partnerLogo(p),
        description: p.description,
        educations: selectedPartnerEducations.value,
        showSectors: false,
        websiteUrl: partnerUrl(p),
    };
});
</script>

<template>
    <Head :title="t('nav.partners')" />

    <SiteLayout>
        <PageIntro :eyebrow="props.event ? t('partners.edition', { name: eventTitle() ?? '' }) : 'ATIx Bedrijvendag'" :title="t('nav.partners')" :lead="t('partners.intro')">
            <template v-if="props.event" #aside>
                <div class="shadow-raised rounded-[var(--radius-card)] bg-canvas p-6 ring-1 ring-hairline">
                    <p class="text-sm text-ink-muted">{{ t('partners.currentEdition') }}</p>
                    <p class="mt-2 text-xl font-semibold tracking-[-0.02em] text-ink">{{ eventTitle() }}</p>
                    <p class="t-mono mt-1 text-sm text-brand-ink">{{ formatDate(props.event.date) }}</p>
                </div>
            </template>
        </PageIntro>

        <div class="site-container pt-16 pb-24 md:pt-24 md:pb-32">
            <section v-for="section in sections" :key="section.key" class="mb-20 last:mb-0 md:mb-28" :aria-labelledby="`partners-${section.key}`">
                <div v-reveal class="grid grid-cols-1 gap-4 border-b border-hairline pb-8 lg:grid-cols-12 lg:items-end">
                    <h2 :id="`partners-${section.key}`" class="t-h2 text-ink lg:col-span-8">
                        {{ section.title }}
                    </h2>
                    <p class="t-body lg:col-span-4 lg:col-start-9">{{ section.description }}</p>
                </div>

                <ul class="mt-8 grid gap-4" :class="section.featured ? 'md:grid-cols-2' : 'sm:grid-cols-2 lg:grid-cols-3'">
                    <li
                        v-for="(partner, index) in section.partners"
                        :key="`${section.key}-${partner.id}`"
                        v-reveal="(index % 3) * 60"
                        class="spot card-lift group relative flex flex-col p-2.5"
                    >
                        <div
                            class="relative flex items-center justify-center overflow-hidden rounded-[var(--radius-input)] bg-white p-10 ring-1 ring-hairline"
                            :class="section.featured ? 'aspect-[16/9]' : 'aspect-[16/10]'"
                        >
                            <img
                                v-if="partnerLogo(partner)"
                                :src="partnerLogo(partner) as string"
                                :alt="partner.name"
                                class="max-h-24 w-auto max-w-[65%] object-contain transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover:scale-[1.06]"
                                loading="lazy"
                                decoding="async"
                            />
                            <span v-else class="text-5xl font-semibold tracking-[-0.05em] text-[#6b7180]" aria-hidden="true">{{ partner.name.charAt(0) }}</span>
                            <span
                                v-if="partner.stand_number"
                                class="t-mono absolute top-3 left-3 rounded-[var(--radius-chip)] bg-[#0b0f19] px-2.5 py-1 text-xs font-medium text-white"
                            >
                                {{ t('common.stand') }} {{ partner.stand_number }}
                            </span>
                            <span
                                class="absolute top-3 right-3 flex size-9 items-center justify-center rounded-full bg-brand text-[#0b0f19] opacity-0 transition-opacity duration-300 group-focus-within:opacity-100 group-hover:opacity-100 max-md:opacity-100"
                                aria-hidden="true"
                            >
                                <PhPlus :size="16" weight="bold" />
                            </span>
                        </div>
                        <div class="flex flex-1 flex-col px-3 pt-5 pb-3">
                            <h3 class="t-h3 text-ink">
                                <button type="button" class="text-left after:absolute after:inset-0 after:rounded-[var(--radius-card)]" @click="openPartner(partner)">
                                    {{ partner.name }}
                                </button>
                            </h3>
                            <p class="t-small mt-2 line-clamp-2">{{ htmlToText(partner.description) || t('partners.noExtraDescription') }}</p>
                            <div class="mt-auto pt-5">
                                <a
                                    v-if="partnerUrl(partner)"
                                    :href="partnerUrl(partner) as string"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="relative z-10 inline-flex items-center gap-1.5 text-sm font-medium text-ink hover:text-brand-ink"
                                >
                                    {{ t('partners.visitWebsite') }}
                                    <PhArrowUpRight :size="14" weight="bold" aria-hidden="true" />
                                </a>
                                <span v-else class="text-sm text-ink-subtle">{{ t('partners.websiteMissing') }}</span>
                            </div>
                        </div>
                    </li>
                </ul>
            </section>

            <EmptyState v-if="!supportPartners.length && !standPartners.length" :icon="PhHandshake" :title="t('partners.none')" />
        </div>
    </SiteLayout>

    <CompanyDialog v-model:open="isPartnerOpen" :company="partnerDialogData" />
</template>
