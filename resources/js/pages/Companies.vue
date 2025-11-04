<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppHeader from '@/components/AppHeader.vue';
import AppFooter from '@/components/AppFooter.vue';
import { Company } from '@/types';
import { ref, computed } from 'vue';

const props = defineProps<{
    companies: Company[];
}>();

const selectedCompany = ref<Company | null>(null);
const visibleCompanies = computed(() => props.companies.filter(c => c.visible));
</script>

<template>
    <Head title="Companies" />

    <AppHeader class="sticky top-0 z-50 bg-primary/8 backdrop-blur supports-[backdrop-filter]:bg-primary/8 dark:bg-[#161615]/80" />
    <header class="flex min-h-[30vh] flex-col items-center justify-center p-6 text-foreground">
        <h1 class="text-3xl md:text-6xl font-bold">Deelnemende Bedrijven</h1>
        <span class="mt-2 block h-1 w-1/2 lg:w-1/4 bg-primary"></span>
        <p class="mb-8 max-w-2xl pt-10 text-center text-gray-600">Bekijk alle bedrijven die aanwezig zullen zijn bij de komende editie op woensdag 19 november 2025.</p>
    </header>

    <!-- Companies (one long list) -->
    <section class="pb-20">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-20 p-6 w-3/4 mx-auto">
            <div v-for="company in visibleCompanies" :key="company.name" class="flex items-center justify-center">
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden w-full">
                    <div class="flex items-center justify-center p-6 bg-blue-500/20">
                        <img :src="company.logo" :alt="company.name" class="h-24 object-contain" />
                    </div>
                    <div class="p-4 border-t">
                        <h3 class="font-semibold text-lg mb-2">{{ company.name }}</h3>
                        <p class="text-sm text-gray-600 line-clamp-3">{{ company.description[0] }}</p>
                        <div class="flex justify-end mt-4">
                            <button @click="selectedCompany = company" class="bg-primary text-white px-4 py-2 rounded hover:bg-primary/90">
                                Meer Lezen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="selectedCompany" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="bg-white dark:bg-gray-900 text-foreground max-w-3xl w-full rounded-lg shadow-lg overflow-y-auto max-h-[90vh] p-6 relative">
                <button @click="selectedCompany = null" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                    ✕
                </button>
                <div class="flex flex-col items-center">
                    <img :src="selectedCompany.logo" :alt="selectedCompany.name" class="h-24 object-contain mb-4" />
                    <h2 class="text-2xl font-bold mb-4">{{ selectedCompany.name }}</h2>
                </div>
                <div class="space-y-3">
                    <p v-for="(desc, i) in selectedCompany.description" :key="i" class="text-gray-700 dark:text-gray-300">
                        {{ desc }}
                    </p>
                </div>
                <div class="mt-6">
                    <h3 class="font-semibold">Opleidingen:</h3>
                    <ul class="list-disc list-inside">
                        <li v-for="(edu, i) in selectedCompany.educations" :key="i">{{ edu }}</li>
                    </ul>
                </div>
                <div class="mt-6">
                    <a :href="selectedCompany.href" target="_blank" class="inline-block bg-primary text-white px-4 py-2 rounded hover:bg-primary/90">
                        Bekijk Website
                    </a>
                </div>
            </div>
        </div>
    </section>

    <AppFooter />
</template>
