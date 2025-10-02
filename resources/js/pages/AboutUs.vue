<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { Head, Link } from '@inertiajs/vue3';
import { BookMarked, ChevronsDown, Handshake, Mic, Network, Wine } from 'lucide-vue-next';
import { OurValues } from '@/types';
import { editions } from '@/routes';

const ourValues: OurValues[] = [
    {
        title: 'Laagdrempelig',
        description: 'Een toegankelijke en informele sfeer waar studenten zich op hun gemak voelen om in contact te komen met bedrijven',
        icon: ChevronsDown,
    },
    {
        title: 'Toekomstgericht',
        description: 'Focus op het verbinden van studenten met bedrijven die stageplaatsen en carrièremogelijkheden bieden',
        icon: BookMarked,
    },
    {
        title: 'Borrelen',
        description: 'Sluit de dag af met een gezellige borrel en netwerk in een informele setting',
        icon: Wine,
    },

    {
        title: 'Netwerken',
        description: 'Breid je netwerk uit en leg waardevolle contacten voor je toekomstige carrière',
        icon: Network,
    },
    {
        title: 'Inspirerend',
        description: 'Laat je inspireren door de verhalen en ervaringen van professionals uit het veld',
        icon: Mic,
    },
    {
        title: 'Samenwerking',
        description: 'Werk samen met medestudenten en professionals om nieuwe kansen te ontdekken',
        icon: Handshake,
    },
];
</script>

<template>
    <Head title="About Us" />

    <AppHeader class="sticky top-0 z-50 bg-primary/8 backdrop-blur supports-[backdrop-filter]:bg-primary/8 dark:bg-[#161615]/80" />
    <header class="relative flex min-h-[25vh] flex-col items-center justify-center">
        <div class="grid min-h-[60vh] w-full grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left side image -->
            <div class="h-full w-full order-2 md:order-1">
                <img src="/images/about-us.jpeg" alt="Bedrijvendag Hero" class="h-full w-full object-cover" />
            </div>
            <!-- Right side text -->
            <div class="flex flex-col justify-center rounded-r-xl bg-white/80 p-6 text-foreground dark:bg-[#161615]/80 order-1 md:order-2">
                <h1 class="relative mt-24 mb-4 text-left text-3xl font-bold text-foreground">
                    Wat is ATIx Bedrijvendag?
                    <span class="mt-2 block h-1 w-1/4 bg-primary"></span>
                </h1>

                <p class="mb-4 font-medium">
                    Jaarlijks organiseren de studievereningen van ATIx twee bedrijvendagen. Tijdens deze dagen krijgen studenten de kans om in contact
                    te komen met het werkveld en meer te ontdekken over hun toekomstige carrière.
                </p>
                <p class="mb-4 font-medium">
                    Het doel van de bedrijvendagen is om studenten te helpen bij het vinden van stageplaatsen en om kennis te maken met bedrijven uit
                    verschillende sectoren. Bedrijven presenteren zich op stands en bieden informatie over hun organisatie, activiteiten en
                    mogelijkheden voor studenten.
                </p>
                <p class="mb-4 font-medium">
                    Voor studenten is dit hét moment om waardevolle connecties te leggen en zich te oriënteren op hun loopbaan, terwijl bedrijven de
                    gelegenheid krijgen om nieuw talent te ontmoeten.
                </p>
            </div>
        </div>
    </header>

    <!-- Onze Waarden -->
    <section class="bg-gray-50 py-16 dark:bg-[#161615]/60">
        <div class="container mx-auto px-6 text-center">
            <h2 class="mb-8 text-3xl font-bold text-foreground">
                Onze Waarden
                <span class="mx-auto mt-2 block h-1 w-24 bg-primary"></span>
            </h2>
            <div class="grid gap-8 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="(value, index) in ourValues" :key="index" class="rounded-lg bg-white p-6 shadow dark:bg-[#161615]">
                    <component :is="value.icon" class="mx-auto mb-4 h-8 w-8 text-primary" />
                    <h3 class="mb-4 text-xl font-semibold text-primary">{{ value.title }}</h3>
                    <p class="text-foreground">{{ value.description }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- volgende evenement Action call -->
    <section class="mx-auto my-12 max-w-4xl rounded-lg bg-primary px-6 py-16 text-center text-white shadow-lg">
        <h2 class="mb-4 text-3xl font-bold">Mis ons volgende evenement niet!</h2>
        <p class="mb-6 text-lg">
            Onze volgende bedrijvendag vindt plaats op <span class="font-semibold">19 november</span>. Een unieke kans om kennis te maken met
            bedrijven en jouw toekomstige carrière vorm te geven.
        </p>
        <Link :href="editions()" class="inline-block rounded bg-white px-6 py-3 font-semibold text-primary transition hover:bg-gray-100">
            Bekijk alle evenementen
        </Link>
    </section>

    <AppFooter />
</template>
