<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { aboutUs, history } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { LockKeyhole, BookOpen, LucideGraduationCap } from 'lucide-vue-next';
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

    <AppHeader class="sticky top-0 z-50 bg-background" />
    <header
        class="relative flex min-h-[70vh] flex-col items-center justify-center bg-accent px-6 text-center lg:px-16"
        style="background-image: url('/images/hero.png'); background-size: cover; background-position: center top"
    >
        <div class="relative z-10 mx-auto -mt-40 text-foreground drop-shadow-lg">
            <p class="mb-3 text-sm font-medium tracking-wide text-foreground">THE GRATEST STUDENT INTERNSHIP FAIR</p>
            <h1 class="mb-6 text-4xl font-bold text-foreground sm:text-5xl md:text-6xl">BEST STUDENT INTERNSHIP FAIR</h1>
            <div class="flex flex-wrap justify-center gap-4">
                <Link
                    class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground hover:bg-primary/75"
                    :href="aboutUs()"
                >
                    LEARN MORE
                </Link>
                <Link
                    class="inline-flex items-center justify-center rounded-md bg-secondary px-6 py-3 text-sm font-semibold text-secondary-foreground hover:bg-secondary/75"
                    :href="history()"
                >
                    PREVIOUS EVENTS
                </Link>
            </div>
        </div>
    </header>

    <!-- Card section -->
    <div class="w-3/4 mx-auto mt-12 mb-12">
        <div class="grid gap-6 grid-cols-1 lg:grid-cols-3">
            <Card class="bg-accent border-0 shadow-sm shadow-foreground/25 hover:bg-primary/60 hover:text-primary-foreground hover:shadow-foreground/50 hover:shadow-lg hover:border-0">
                <CardHeader>
                    <LucideGraduationCap class="size-12" />
                    <CardTitle class="mt-3 text-2xl"> Studenten Contact </CardTitle>
                </CardHeader>
                <CardContent> Studenten van Mechatronica, Werktuigbouwkunde, (Technisch) Informatica, Elektrotechniek, Bedrijfs Informatica en Management </CardContent>
            </Card>
            <Card class="bg-accent border-0 shadow-sm shadow-foreground/25 hover:bg-primary/60 hover:text-primary-foreground hover:shadow-foreground/50 hover:shadow-lg hover:border-0">
                <CardHeader>
                    <BookOpen class="size-12" />
                    <CardTitle class="mt-3 text-2xl"> Kennis Delen </CardTitle>
                </CardHeader>
                <CardContent> Orienteren op het werkveld, zie wat mogelijk is en welke bedrijven er in jou sector zitten  </CardContent>
            </Card>
            <Card class="bg-accent border-0 shadow-sm shadow-foreground/25 hover:bg-primary/60 hover:text-primary-foreground hover:shadow-foreground/50 hover:shadow-lg hover:border-0">
                <CardHeader>
                    <LucideGraduationCap class="size-12" />
                    <CardTitle class="mt-3 text-2xl"> Studenten kennis </CardTitle>
                </CardHeader>
                <CardContent> Kom in contact met de studenten van Avans engineering </CardContent>
            </Card>
        </div>
    </div>

    <!--<div class="flex min-h-screen flex-col items-stretch p-6 text-[#1b1b18] lg:p-8">
        <div class="hidden h-14.5 lg:block"></div>
    </div>-->
    <AppFooter />
</template>
