<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import { companies, dashboard } from '@/routes';

const props = defineProps<{
    company: {
        id: number;
        name: string;
        description?: string;
        href?: string;
        logo?: string;
        visible: boolean;
        sectors: string[];
        editions: string[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Companies',
        href: companies().url,
    },
    {
        title: props.company.name,
        href: '',
    },
];
</script>

<template>
    <Head :title="`View ${props.company.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex justify-center items-center py-10 px-6">
            <div class="w-full max-w-3xl bg-white dark:bg-gray-900 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 p-8 space-y-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                    <img
                        v-if="props.company.logo"
                        :src="props.company.logo"
                        alt="Company logo"
                        class="h-24 w-24 rounded-lg object-cover border border-gray-300 dark:border-gray-600 shadow-sm"
                    />
                    <div class="flex w-full items-center justify-between text-center sm:text-left">
                        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">
                            {{ props.company.name }}
                        </h1>
                        <span
                            class="ml-4 px-4 py-1.5 rounded-full text-sm font-medium select-none"
                            :class="props.company.visible
                          ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                          : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'"
                        >
                        {{ props.company.visible ? 'Visible' : 'Hidden' }}
                      </span>
                    </div>
                </div>

                <!-- Description -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">About</h2>
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">
                        {{ props.company.description || 'No description provided.' }}
                    </p>
                </div>

                <!-- Sectors and Editions -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3">Sectors</h2>
                        <div v-if="props.company.sectors.length" class="flex flex-wrap gap-2">
              <span
                  v-for="sector in props.company.sectors"
                  :key="sector"
                  class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-100"
              >
                {{ sector }}
              </span>
                        </div>
                        <p v-else class="text-gray-500">No sectors specified.</p>
                    </div>

                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3">Editions</h2>
                        <div v-if="props.company.editions.length" class="flex flex-wrap gap-2">
              <span
                  v-for="edition in props.company.editions"
                  :key="edition"
                  class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-100"
              >
                {{ edition }}
              </span>
                        </div>
                        <p v-else class="text-gray-500">No editions assigned.</p>
                    </div>
                </div>

                <!-- Website Link -->
                <div v-if="props.company.href" class="pt-6 border-t border-gray-200 dark:border-gray-700 text-center">
                    <a
                        :href="props.company.href"
                        target="_blank"
                        class="inline-block rounded-md bg-primary px-5 py-2 text-white font-medium hover:bg-primary/90 transition-colors"
                    >
                        Visit Company Website
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
