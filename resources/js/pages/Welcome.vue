<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { aboutUs } from '@/routes';
import type { OurValues } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BookMarked, ChevronsDown, Compass, DoorOpen, Handshake, Mic, Network, Users, Wine } from 'lucide-vue-next';
import { marked } from 'marked';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{ editions: any[] }>();

const countdown = ref({ weken: 0, dagen: 0, uur: 0, minuten: 0, seconden: 0 });
const totalDays = ref(0);
const finished = ref(false);
const target = new Date('2025-11-19T13:00:00').getTime();
let timer: number;

const update = () => {
    const d = target - Date.now();
    if (d <= 0) {
        countdown.value = { weken: 0, dagen: 0, uur: 0, minuten: 0, seconden: 0 };
        totalDays.value = 0;
        finished.value = true;
        clearInterval(timer);
        return;
    }

    finished.value = false;

    const td = Math.floor(d / 864e5);
    const weeks = Math.floor(td / 7);
    const days = td % 7;
    const hours = Math.floor((d % 864e5) / 36e5);
    const minutes = Math.floor((d % 36e5) / 6e4);
    const seconds = Math.floor((d % 6e4) / 1000);

    countdown.value = {
        weken: weeks,
        dagen: days,
        uur: hours,
        minuten: minutes,
        seconden: seconds,
    };
    totalDays.value = td;
};

onMounted(() => {
    update();
    timer = setInterval(update, 1000);
});
onUnmounted(() => clearInterval(timer));

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

const form = useForm({
    name: '',
    email: '',
});

const showMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);

const submit = () => {
    form.post('/borrel-enrollment', {
        preserveScroll: true,
        onSuccess: () => {
            showMessage.value = { type: 'success', text: 'Bedankt voor je inschrijving!' };
            form.reset();
            setTimeout(() => (showMessage.value = null), 10000);
        },
        onError: () => {
            showMessage.value = { type: 'error', text: 'Er is iets misgegaan. Probeer het opnieuw.' };
            setTimeout(() => (showMessage.value = null), 10000);
        },
    });
};
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
                    href="/editions"
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
                            >Spreek studenten van Mechatronica, Werktuigbouwkunde, (Technische) Informatica, Elektrotechniek, Business IT & Management,
                            Technische Bedrijfskunde en Industrial Engineering & Management
                        </CardContent>
                    </div>
                </CardHeader>
            </Card>
            <Card
                class="border-0 bg-accent shadow-sm shadow-foreground/25 hover:border-0 hover:bg-primary/60 hover:text-primary-foreground hover:shadow-lg hover:shadow-foreground/50"
            >
                <CardHeader class="flex flex-row items-center gap-4">
                    <Compass class="size-12 flex-shrink-0" />
                    <div>
                        <CardTitle class="mt-0 text-2xl">Oriënteren</CardTitle>
                        <CardContent class="p-0">
                            Oriënteer bij bedrijven van jouw opleiding en neem de volgende stap in het zoeken van een stageplaats
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
            <h2 class="mb-2 text-3xl font-extrabold">Laatste Edities</h2>
            <p class="text-lg text-muted-foreground">Bekijk de drie meest recente edities</p>
        </div>
        <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <template v-for="(item, index) in props.editions" :key="index">
                <div class="flex flex-col overflow-hidden rounded-lg bg-blue-100/50 shadow">
                    <img v-if="item.thumbnail" :src="item.thumbnail" alt="Event image" class="aspect-[16/16] w-full object-cover object-top" />
                    <div class="flex-1 p-6">
                        <h3 class="mb-2 text-lg font-semibold">{{ item.name }}</h3>
                        <p class="text-sm text-muted-foreground" v-html="marked.parse(item.description)" />
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 border-t bg-gray-50 px-6 py-4">
                        <span class="text-sm text-muted-foreground">
                            {{
                                new Date(item.date).toLocaleDateString('nl-NL', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' })
                            }}
                        </span>
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
                <h2 class="mb-4 text-3xl font-extrabold">Aanmelden Afsluitende Borrel</h2>
                <p class="mb-6 text-lg opacity-90">
                    Schrijf je als student in voor de ATIx Bedrijvendag en krijg toegang tot de afsluitende borrel! Na een dag vol inspiratie en netwerken
                    is er niets beter dan ontspannen en verder netwerken tijdens onze gezellige borrel.
                </p>
                <p class="mb-2 text-lg font-bold">Nog maar:</p>
                <div class="flex justify-center gap-4 lg:justify-start w-full">
                    <template v-if="finished">
                        <div class="w-full rounded-md border-1 border-background bg-primary-foreground/10 px-6 py-4 text-center">
                            <p class="text-2xl font-bold">Happy Connecting!</p>
                        </div>
                    </template>

                    <template v-else>
                        <template v-if="countdown.weken > 0">
                            <div class="min-w-1/3 rounded-md border-1 border-background bg-primary-foreground/10 px-6 py-4 text-center">
                                <p class="text-2xl font-bold">{{ countdown.weken }}</p>
                                <p class="text-sm">Weken</p>
                            </div>
                            <div class="min-w-1/3 rounded-md border-1 border-background bg-primary-foreground/10 px-6 py-4 text-center">
                                <p class="text-2xl font-bold">{{ countdown.dagen }}</p>
                                <p class="text-sm">Dagen</p>
                            </div>
                            <div class="min-w-1/3 rounded-md border-1 border-background bg-primary-foreground/10 px-6 py-4 text-center">
                                <p class="text-2xl font-bold">{{ countdown.uur }}</p>
                                <p class="text-sm">Uur</p>
                            </div>
                        </template>

                        <template v-else-if="countdown.dagen > 0">
                            <div class="min-w-1/3 rounded-md border-1 border-background bg-primary-foreground/10 px-6 py-4 text-center">
                                <p class="text-2xl font-bold">{{ countdown.dagen }}</p>
                                <p class="text-sm">Dagen</p>
                            </div>
                            <div class="min-w-1/3 rounded-md border-1 border-background bg-primary-foreground/10 px-6 py-4 text-center">
                                <p class="text-2xl font-bold">{{ countdown.uur }}</p>
                                <p class="text-sm">Uur</p>
                            </div>
                            <div class="min-w-1/3 rounded-md border-1 border-background bg-primary-foreground/10 px-6 py-4 text-center">
                                <p class="text-2xl font-bold">{{ countdown.minuten }}</p>
                                <p class="text-sm">Minuten</p>
                            </div>
                        </template>

                        <template v-else>
                            <div class="min-w-1/3 rounded-md border-1 border-background bg-primary-foreground/10 px-6 py-4 text-center">
                                <p class="text-2xl font-bold">{{ countdown.uur }}</p>
                                <p class="text-sm">Uur</p>
                            </div>
                            <div class="min-w-1/3 rounded-md border-1 border-background bg-primary-foreground/10 px-6 py-4 text-center">
                                <p class="text-2xl font-bold">{{ countdown.minuten }}</p>
                                <p class="text-sm">Minuten</p>
                            </div>
                            <div class="min-w-1/3 rounded-md border-1 border-background bg-primary-foreground/10 px-6 py-4 text-center">
                                <p class="text-2xl font-bold">{{ countdown.seconden }}</p>
                                <p class="text-sm">Seconden</p>
                            </div>
                        </template>
                    </template>
                </div>
            </div>
            <!-- Form or success/error message -->
            <div class="w-full max-w-md rounded-lg bg-white p-8 shadow text-center">
                <template v-if="showMessage">
                    <div
                        :class="[
                            'rounded-md p-6 font-semibold',
                            showMessage.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                        ]"
                    >
                        {{ showMessage.text }}
                    </div>
                </template>

                <template v-else>
                    <h3 class="mb-2 text-xl font-bold">Inschrijven Studenten</h3>
                    <p class="mb-6 text-gray-500">Het is tijd om deel te nemen</p>
                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium">Naam</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/50"
                                placeholder="Voor- en achternaam"
                            />
                            <span v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">E-mail</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/50"
                                placeholder="naam@student.avans.nl"
                            />
                            <span v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</span>
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="form.processing" size="lg" variant="secondary" class="text-md font-bold">
                                Versturen
                            </Button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>

    <section class="mx-auto mb-24 w-10/16 text-center">
        <h2 class="mb-12 text-3xl font-extrabold">Onze Waarden</h2>
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
