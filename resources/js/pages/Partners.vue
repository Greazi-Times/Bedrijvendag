<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppHeader from '@/components/AppHeader.vue';
import AppFooter from '@/components/AppFooter.vue';
import type { Partner } from '@/types';

const partners: Partner[] = [
    {
        name: "SV-Motus",
        logo: "/images/partners/svmotus.png",
        href: "https://sv-motus.nl/",
        visible: true,
    },
    {
        name: "Promptus Imperii",
        logo: "/images/partners/pi.png",
        href: "https://svpromptusimperii.nl/",
        visible: true,
    },
    {
        name: "SV-WIM",
        logo: "/images/partners/svwim.png",
        href: "https://svwim.nl/",
        visible: true,
    },
    {
        name: "Silicium",
        logo: "/images/partners/silicium.png",
        href: "https://www.svsilicium.nl/",
        visible: true,
    },
    {
        name: "Virgo",
        logo: "/images/partners/virgo.png",
        href: "https://sv-virgo.nl/",
        visible: true,
    },
    {
        name: "SV-Connect",
        logo: "/images/partners/svconnect.png",
        href: "https://svconnect.nl/",
        visible: true,
    },
    {
        name: "Maximus",
        logo: "/images/partners/maximus.png",
        href: "https://svmaximus.nl/",
        visible: true,
    },
    {
        name: "Avans Hogeschool",
        logo: "/images/partners/avans.png",
        href: "https://avans.nl/",
        visible: true,
    },
];
</script>

<template>
    <Head title="Companies" />

    <AppHeader class="sticky top-0 z-50 bg-primary/8 backdrop-blur supports-[backdrop-filter]:bg-primary/8 dark:bg-[#161615]/80" />
    <header class="flex min-h-[30vh] flex-col items-center justify-center p-6 text-foreground">
        <h1 class="text-6xl font-bold">Partners</h1>
        <span class="mt-2 block h-1 w-1/4 bg-primary"></span>
        <p class="mb-8 max-w-2xl pt-10 text-center text-gray-600">
            Dankzij onderstaande partners kunnen wij elk jaar een succesvolle ATIx Bedrijvendag organiseren.
        </p>
    </header>

    <section class="pb-10">
        <div class="mx-auto w-[70%] flex flex-wrap justify-center gap-8 p-6">
          <div
            v-for="partner in partners.filter(p => p.visible)"
            :key="partner.name"
            class="flex w-full sm:w-[45%] lg:w-[30%] flex-col items-center rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800"
          >
            <img
              :src="partner.logo"
              :alt="partner.name"
              class="mb-4 h-24 object-contain"
            />
            <h2 class="mb-2 text-lg font-semibold text-gray-800 dark:text-gray-200">
              {{ partner.name }}
            </h2>
            <a
              :href="partner.href"
              target="_blank"
              rel="noopener"
              class="mt-4 inline-block rounded bg-primary px-4 py-2 text-white hover:bg-primary/80"
            >
              Bezoek Website
            </a>
          </div>
        </div>
    </section>

    <AppFooter />
</template>
