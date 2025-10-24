<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { dashboard, editions } from '@/routes';

interface Edition {
    id: number;
    name: string;
    description: string;
    date: string; // ISO or readable date string
    images?: string; // Google Photos album URL
    thumbnail?: string; // image path/url
}

const props = defineProps<{ edition: Edition }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Editions', href: editions().url },
    { title: props.edition.name, href: '' },
];

const formattedDate = computed(() => {
    const d = new Date(props.edition.date);
    if (isNaN(d.getTime())) return props.edition.date;
    try {
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    } catch {
        return d.toLocaleDateString();
    }
});

const albumUrl = computed(() => (props.edition.images || '').trim());
</script>

<template>
    <Head :title="`View ${props.edition.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex justify-center items-center py-10 px-6">
            <div class="w-full max-w-3xl bg-white dark:bg-gray-900 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 p-8 space-y-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                    <img
                        v-if="props.edition.thumbnail"
                        :src="props.edition.thumbnail"
                        alt="Edition thumbnail"
                        class="h-24 w-24 rounded-lg object-cover border border-gray-300 dark:border-gray-600 shadow-sm"
                    />
                    <div class="flex w-full items-center justify-between text-center sm:text-left">
                        <div>
                            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">
                                {{ props.edition.name }}
                            </h1>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ID: {{ props.edition.id }}</p>
                        </div>
                        <span
                            class="ml-4 px-4 py-1.5 rounded-full text-sm font-medium select-none bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100"
                        >
              {{ formattedDate }}
            </span>
                    </div>
                </div>

                <!-- Description -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">About</h2>
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">
                        {{ props.edition.description || 'No description provided.' }}
                    </p>
                </div>

                <!-- Album link -->
                <div v-if="albumUrl" class="pt-6 border-t border-gray-200 dark:border-gray-700 text-center">
                    <a
                        :href="albumUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-block rounded-md bg-primary px-5 py-2 text-white font-medium hover:bg-primary/90 transition-colors"
                    >
                        Open Google Photos Album
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
