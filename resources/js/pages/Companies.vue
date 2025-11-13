<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { Company } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { marked } from 'marked';

const props = defineProps<{
    companies: (Company & { stand_display?: string | null })[];
}>();

const selectedCompany = ref<Company | null>(null);
const visibleCompanies = computed(() => {
    const shuffled = [...props.companies].filter((c) => c.visible).sort(() => Math.random() - 0.5);
    return shuffled;
});

const searchQuery = ref('');
const selectedSectors = ref<string[]>([]);

const allSectors = computed(() => {
    const sectors = new Set<string>();
    props.companies.forEach((company) => {
        company.educations?.forEach((s) => sectors.add(s));
    });
    return Array.from(sectors).sort();
});

const filteredCompanies = computed(() => {
    return visibleCompanies.value.filter((company) => {
        const matchesSearch = company.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesSector =
            selectedSectors.value.length === 0 ||
            company.educations.some((s) => selectedSectors.value.includes(s));
        return matchesSearch && matchesSector;
    });
});

const toggleSector = (sector: string) => {
    if (selectedSectors.value.includes(sector)) {
        selectedSectors.value = selectedSectors.value.filter((s) => s !== sector);
    } else {
        selectedSectors.value.push(sector);
    }
};
</script>

<template>
    <Head title="Companies" />

    <AppHeader class="sticky top-0 z-50 bg-primary/8 backdrop-blur supports-[backdrop-filter]:bg-primary/8 dark:bg-[#161615]/80" />
    <header class="flex min-h-[30vh] flex-col items-center justify-center p-6 text-foreground">
        <h1 class="text-3xl font-bold md:text-6xl">Deelnemende Bedrijven</h1>
        <span class="mt-2 block h-1 w-1/2 bg-primary lg:w-1/4"></span>
        <p class="mb-8 max-w-2xl pt-10 text-center text-gray-600">
            Bekijk alle bedrijven die aanwezig zullen zijn bij de komende editie op woensdag 19 november 2025.
        </p>
    </header>

    <section class="flex flex-col items-center justify-center gap-4 pb-15">
        <!-- Search bar -->
        <input
            v-model="searchQuery"
            type="text"
            placeholder="Zoek op bedrijfsnaam..."
            class="w-2/4 rounded-lg border border-gray-300 p-3 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
        />

        <!-- Sector filter buttons -->
        <div class="flex flex-wrap justify-center gap-2 w-2/4 pt-2">
            <button
                v-for="sector in allSectors"
                :key="sector"
                @click="toggleSector(sector)"
                :class="[
                    'rounded-full px-4 py-2 text-sm font-medium transition',
                    selectedSectors.includes(sector)
                        ? 'bg-primary text-white'
                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                ]"
            >
                {{ sector }}
            </button>
        </div>
    </section>

    <!-- Companies (one long list) -->
    <section class="pb-20">
        <div class="mx-auto grid w-3/4 grid-cols-1 gap-20 p-6 md:grid-cols-2">
            <div v-for="company in filteredCompanies" :key="company.name" class="flex items-center justify-center">
                <div class="w-full overflow-hidden rounded-lg bg-white shadow transition hover:shadow-lg">
                    <!-- make image container relative to position the small circle -->
                    <div class="relative flex items-center justify-center bg-blue-500/20 p-6">
                        <img :src="company.logo" :alt="company.name" class="h-24 object-contain" />
                        <div
                            v-if="company.stand_display"
                            class="absolute bottom-3 right-3 flex h-7 w-7 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-semibold"
                            aria-hidden="true"
                        >
                            {{ company.stand_display }}
                        </div>
                    </div>
                    <div class="border-t p-4">
                        <h3 class="mb-2 text-lg font-semibold">{{ company.name }}</h3>
                        <div class="line-clamp-3 text-sm text-gray-600" v-html="marked(company.description[0])"></div>
                        <div class="mt-4 flex justify-end">
                            <button @click="selectedCompany = company" class="rounded bg-primary px-4 py-2 text-white hover:bg-primary/90">
                                Meer Lezen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="selectedCompany" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-white p-6 text-foreground shadow-lg dark:bg-gray-900">
                <button @click="selectedCompany = null" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                    ✕
                </button>
                <div class="flex flex-col items-center">
                    <div class="relative">
                        <img :src="selectedCompany.logo" :alt="selectedCompany.name" class="mb-4 h-24 object-contain" />
                        <!-- modal badge: small circle near image if stand exists -->
                        <div
                            v-if="selectedCompany.stand_display"
                            class="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-semibold"
                            aria-hidden="true"
                        >
                            {{ selectedCompany.stand_display }}
                        </div>
                    </div>
                    <h2 class="mb-4 text-2xl font-bold flex items-center">
                        {{ selectedCompany.name }}
                    </h2>
                </div>
                <div class="space-y-3">
                    <div v-for="(desc, i) in selectedCompany.description" :key="i" v-html="marked(desc)" class="text-gray-700 dark:text-gray-300"></div>
                </div>
                <div class="mt-6">
                    <h3 class="font-semibold">Opleidingen:</h3>
                    <ul class="list-inside list-disc">
                        <li v-for="(edu, i) in selectedCompany.educations" :key="i">{{ edu }}</li>
                    </ul>
                </div>
                <div class="mt-6">
                    <a :href="selectedCompany.href" target="_blank" class="inline-block rounded bg-primary px-4 py-2 text-white hover:bg-primary/90">
                        Bekijk Website
                    </a>
                </div>
            </div>
        </div>
    </section>

    <AppFooter />
</template>
