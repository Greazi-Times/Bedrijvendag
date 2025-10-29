<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AppHeader from '@/components/AppHeader.vue';
import AppFooter from '@/components/AppFooter.vue';
import type { Edition } from '@/types';
import { marked } from 'marked';

const { props } = usePage();
const editions = (props.editions as Edition[]).sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('nl-NL', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const renderMarkdown = (text: string) => {
    if (!text) return '';

    // Replace Windows-style newlines
    const normalized = text.replace(/\r\n/g, '\n');

    // Ensure Markdown treats double newlines as paragraph breaks
    // and single newlines as line breaks
    const html = marked.parse(normalized, {
        breaks: true, // convert single \n to <br>
    });

    return html;
};
</script>

<template>
    <Head title="Editions" />

    <AppHeader class="sticky top-0 z-50 bg-primary/8 backdrop-blur supports-[backdrop-filter]:bg-primary/8 dark:bg-[#161615]/80" />
    <header class="flex min-h-[30vh] flex-col items-center justify-center p-6 text-foreground">
        <h1 class="text-6xl font-bold">Edities</h1>
        <span class="mt-2 block h-1 w-1/4 bg-primary"></span>
        <p class="mb-8 max-w-2xl pt-10 text-center text-gray-600">Bekijk al onze voorgaande en toekomstige edities van de ATIx Bedrijvendag.</p>
    </header>

    <!-- Edities -->
    <section>
        <div class="mx-auto grid max-w-7xl gap-20 px-6 py-12 sm:grid-cols-2 lg:grid-cols-2">
            <div v-for="edition in editions" :key="edition.name" class="overflow-hidden rounded-lg bg-white shadow flex flex-col">
                <img class="w-full aspect-[4/3] object-cover" :src="edition.thumbnail" :alt="edition.name" />
                <div class="flex-1 bg-blue-50 p-4">
                    <h2 class="text-lg font-semibold">{{ edition.name }}</h2>
                    <div class="mt-1 text-gray-600 prose prose-sm max-w-none" v-html="renderMarkdown(edition.description)"></div>
                </div>
                <div class="flex items-center justify-between border-t bg-white px-4 py-3">
                    <span class="text-sm text-gray-500">{{ formatDate(edition.date) }}</span>
                    <a :href="edition.images" class="rounded bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600">Bekijk Afbeeldingen</a>
                </div>
            </div>
        </div>
    </section>

    <AppFooter />
</template>
