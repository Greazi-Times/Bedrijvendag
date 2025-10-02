<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { aboutUs, editions } from '@/routes';
import { Form, Head, Link } from '@inertiajs/vue3';
import { BookMarked, ChevronsDown, Compass, DoorOpen, Handshake, Mic, Network, Users, Wine } from 'lucide-vue-next';
import type { OurValues, PreviousEvent } from '@/types';
import { ref, onMounted, onUnmounted } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

const countdown = ref({ weken: 0, dagen: 0, uur: 0 });
const totalDays = ref(0);
const target = new Date('2025-11-12T23:59:59').getTime();
let timer: number;

const update = () => {
    const d = target - Date.now();
    if (d <= 0) {
        countdown.value = { weken: 0, dagen: 0, uur: 0 };
        totalDays.value = 0;
        clearInterval(timer);
        return;
    }
    const td = Math.floor(d / 864e5);
    countdown.value = {
        weken: Math.floor(td / 7),
        dagen: td % 7,
        uur: Math.floor((d % 864e5) / 36e5),
    };
    totalDays.value = td;
};

onMounted(() => { update(); timer = setInterval(update, 1000); });
onUnmounted(() => clearInterval(timer));

const previousEvents: PreviousEvent[] = [
    {
        title: '2de Editie 2025',
        description: 'De komende bedrijvendag',
        date: '19 november 2025',
        image: 'images/editie-2.jpg',
        href: 'string',
    },
    {
        title: '1st Editie 2025',
        description: 'Geanuleerd wegens omstandigheden',
        date: '12 november 2024',
        image: '/images/editie-1.jpg',
        href: 'string',
    },
    {
        title: '2de Editie 2024',
        description: 'Begin schooljaar 2024-2025',
        date: '12 november 2024',
        image: '/images/editie-3.jpg',
        href: 'string',
    },
];

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
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        <link
            rel="preload"
            as="image"
            href="/images/bbq-hero.avif"
            imagesrcset="/images/bbq-hero.avif 1x, /images/bbq-hero@2x.avif 2x"
            imagesizes="100vw"
        />
    </Head>

    <AppHeader class="sticky top-0 z-50" />
    <header
        class="relative flex min-h-[70vh] flex-col items-center justify-center bg-blue-500/25 px-6 text-center lg:px-16"
        style="background-image: url('/images/hero.png'); background-size: cover; background-position: center top"
    >
        <div class="relative z-10 mx-auto -mt-40 text-foreground drop-shadow-lg">
            <p class="mb-3 text-sm font-medium tracking-wide text-foreground text-shadow-foreground/50 text-shadow-md">
                VOOR STUDENTEN & DOOR STUDENTEN
            </p>
            <h1 class="mb-6 text-4xl font-bold text-foreground text-shadow-foreground/50 text-shadow-md sm:text-5xl md:text-6xl">
                DE ATIx BEDRIJVENDAG
            </h1>
            <div class="flex flex-wrap justify-center gap-4">
                <Link
                    class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground hover:bg-primary/75"
                    :href="aboutUs()"
                >
                    MEER WETEN
                </Link>
                <Link
                    class="inline-flex items-center justify-center rounded-md bg-secondary px-6 py-3 text-sm font-semibold text-secondary-foreground hover:bg-secondary/75"
                    :href="editions()"
                >
                    VORIGE EDITIES
                </Link>
            </div>
        </div>
    </header>

    <!-- Card section -->
    <div class="mx-auto mt-12 mb-24 w-3/4">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <Card
                class="border-0 bg-accent shadow-sm shadow-foreground/25 hover:border-0 hover:bg-primary/60 hover:text-primary-foreground hover:shadow-lg hover:shadow-foreground/50"
            >
                <CardHeader class="flex flex-row items-center gap-4">
                    <Users class="size-12 flex-shrink-0" />
                    <div>
                        <CardTitle class="mt-0 text-2xl">Contact</CardTitle>
                        <CardContent class="p-0"
                            >Spreek studenten van Mechatronica, Werktuigbouwkunde, (Technisch) Informatica, Elektrotechniek, Business IT &
                            Management</CardContent
                        >
                    </div>
                </CardHeader>
            </Card>
            <Card
                class="border-0 bg-accent shadow-sm shadow-foreground/25 hover:border-0 hover:bg-primary/60 hover:text-primary-foreground hover:shadow-lg hover:shadow-foreground/50"
            >
                <CardHeader class="flex flex-row items-center gap-4">
                    <Compass class="size-12 flex-shrink-0" />
                    <div>
                        <CardTitle class="mt-0 text-2xl">Orienteren</CardTitle>
                        <CardContent class="p-0">
                            Orienteer bij bedrijven van jouw opleiding en neem de volgende stap in het zoeken van een stageplaats
                        </CardContent>
                    </div>
                </CardHeader>
            </Card>
            <Card
                class="border-0 bg-accent shadow-sm shadow-foreground/25 hover:border-0 hover:bg-primary/60 hover:text-primary-foreground hover:shadow-lg hover:shadow-foreground/50"
            >
                <CardHeader class="flex flex-row items-center gap-4">
                    <DoorOpen class="size-12 flex-shrink-0" />
                    <div>
                        <CardTitle class="mt-0 text-2xl">Vrije Inloop</CardTitle>
                        <CardContent class="p-0">
                            Studenten lopen vrij rond over de bedrijvendag, maak een praatje en kom meer te weten over het bedrijf
                        </CardContent>
                    </div>
                </CardHeader>
            </Card>
        </div>
    </div>

    <!-- Previous editions -->
    <div class="mx-auto mt-24 mb-24 w-3/4 text-center">
        <div>
            <h2 class="mb-2 text-3xl font-extrabold">Voorgaande Edities</h2>
            <p class="text-lg text-muted-foreground">Bekijk al onze voorgaande edities</p>
        </div>
        <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <template v-for="(item, index) in previousEvents" :key="index">
                <div class="flex flex-col overflow-hidden rounded-lg bg-blue-100/50 shadow">
                    <img :src="item.image" alt="Event image" class="aspect-[16/16] w-full object-cover object-top" />
                    <div class="flex-1 p-6">
                        <h3 class="mb-2 text-lg font-semibold">{{ item.title }}</h3>
                        <p class="text-sm text-muted-foreground">{{ item.description }}</p>
                    </div>
                    <div class="flex items-center justify-between border-t bg-gray-50 px-6 py-4">
                        <span class="text-sm text-muted-foreground">{{ item.date }}</span>
                        <Link
                            :href="item.href"
                            class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/75"
                        >
                            Meer Lezen
                        </Link>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Call to enroll -->
    <div class="mx-auto mb-24 w-full bg-primary py-16">
        <div class="mx-auto flex w-full max-w-5xl flex-col items-center justify-between gap-16 px-6 lg:flex-row">
            <!-- Left text and countdown placeholder -->
            <div class="min-w-5/8 flex-1 text-center text-primary-foreground lg:text-left">
                <h2 class="mb-4 text-3xl font-extrabold">Nu Aanmelden</h2>
                <p class="mb-6 text-lg opacity-90">
                    Schrijf je in als bedrijf en ontmoet gemotiveerde studenten die op zoek zijn naar een stageplaats!
                </p>
                <p class="mb-2 text-lg font-bold">
                    Nog maar:
                </p>
                <div class="flex justify-center gap-4 lg:justify-start">
                    <template v-for="(value, label) in countdown" :key="label">
                        <div class="min-w-1/3 rounded-md border-1 border-background bg-primary-foreground/10 px-6 py-4 text-center">
                            <p class="text-2xl font-bold">{{ value }}</p>
                            <p class="text-sm">{{ label.charAt(0).toUpperCase() + label.slice(1) }}</p>
                        </div>
                    </template>
                </div>
            </div>
            <!-- Form box -->
            <div class="w-full max-w-md rounded-lg bg-white p-8 shadow">
                <h3 class="mb-2 text-xl font-bold">Inschrijven</h3>
                <p class="mb-6 text-gray-500">Het is tijd om deel te nemen</p>
                <Form class="mb-6 space-y-4">
                    <Input type="text" placeholder="Bedrijfsnaam" />
                    <Input type="email" placeholder="E-mail" />
                    <Button size="lg" variant="secondary" class="text-md w-full font-bold">Versturen</Button>
                </Form>
            </div>
        </div>
    </div>

    <section class="mx-auto mb-24 w-10/16 text-center">
        <h2 class="mb-12 text-3xl font-extrabold">Onze Waardens</h2>
        <div class="grid grid-cols-1 gap-12 text-left md:grid-cols-2 lg:grid-cols-3">
            <template v-for="(value, idx) in ourValues" :key="idx">
                <div>
                    <div class="mb-4 flex justify-center text-primary md:justify-start">
                        <component :is="value.icon" class="size-8 lg:size-14" />
                    </div>
                    <h3 class="mb-2 text-lg font-bold">{{ value.title }}</h3>
                    <p class="text-muted-foreground">
                        {{ value.description }}
                    </p>
                </div>
            </template>
        </div>
    </section>

    <AppFooter />
</template>
