<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppHeader from '@/components/AppHeader.vue';
import AppFooter from '@/components/AppFooter.vue';
import type { Edition } from '@/types';

const editions: Edition[] = [
    {
        name: '2de Editie 2025',
        description: 'De tweede editie van de ATIx Bedrijvendag vindt plaats op woensdag 19 november 2025. Bereid je voor op een dag vol inspirerende gesprekken, waardevolle connecties en spannende mogelijkheden voor jouw toekomst.',
        image: '/images/editie-2.jpg',
        date: 'Woensdag 19 november 2025',
        href: 'https://sv-motus.nl/',
    },
    {
        name: '1ste Editie 2025',
        description: 'Deze editie is helaas door omstandigheden geanuleerd, maar we kijken ernaar uit je te verwelkomen op onze volgende editie in november 2025!',
        image: '/images/editie-1.jpg',
        date: 'Geannuleerd',
        href: 'https://sv-motus.nl/',
    },
    {
        name: '2de Editie 2024',
        description: 'De tweede editie van de ATIx Bedrijvendag vond plaats op woensdag 20 november 2024. Het was een dag vol inspirerende gesprekken, waardevolle connecties en spannende mogelijkheden voor de toekomst van vele studenten.',
        image: '/images/editie-3.jpg',
        date: 'Geen idee weet jij het?',
        href: 'https://sv-motus.nl/',
    },
];
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
          <img class="w-full aspect-[4/3] object-cover" :src="edition.image" :alt="edition.name" />
          <div class="flex-1 bg-blue-50 p-4">
            <h2 class="text-lg font-semibold">{{ edition.name }}</h2>
            <p class="mt-1 text-gray-600">{{ edition.description }}</p>
          </div>
          <div class="flex items-center justify-between border-t bg-white px-4 py-3">
            <span class="text-sm text-gray-500">{{ edition.date }}</span>
            <a :href="edition.href" class="rounded bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600">Meer Lezen</a>
          </div>
        </div>
      </div>
    </section>

    <AppFooter />
</template>
