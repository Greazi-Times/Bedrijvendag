<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue';
import { history } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import AppFooter from '@/components/AppFooter.vue';
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        <link rel="preload" as="image" href="/images/bbq-hero.avif" imagesrcset="/images/bbq-hero.avif 1x, /images/bbq-hero@2x.avif 2x" imagesizes="100vw" />
    </Head>

    <AppHeader class="sticky top-0 z-50 bg-background" />
    <header
        class="relative mb-10 min-h-[calc(100vh-64px)] w-full overflow-hidden px-6 lg:px-16 bg-cover bg-center grid place-items-center"
        style="background-image: url('/images/bbq-hero.png')"
    >
        <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/20 to-black/40"></div>
        <div class="relative mx-auto grid w-full max-w-[1200px] grid-cols-1 items-center gap-10 lg:grid-cols-2 rounded-2xl p-8 lg:p-10 bg-black/10 ring-1 ring-white/10 backdrop-blur-sm">
            <!-- Left column: text -->
            <div class="space-y-6 text-white">
                <div class="flex items-center gap-3 text-sm">
                    <span class="font-bold">Bekijk onze laatste Barbecue's</span>
                    <Link
                        :href="history()"
                        class="inline-flex items-center gap-1 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium hover:bg-white/15"
                    >
                        <span>BEKIJKEN</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                            <path
                                fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </Link>
                </div>

                <h1 class="leading-tight font-semibold tracking-tight text-5xl sm:text-6xl flex items-baseline gap-3">
                    <span>Eindejaars</span>
                    <span class="bg-gradient-to-r from-primary to-accent-foreground bg-clip-text text-transparent">BBQ</span>
                </h1>

                <p class="max-w-xl text-base font-bold">
                    De beste barbecue van het jaar!
                </p>
                <div class="flex flex-wrap gap-3 pt-3">
                    <a
                        href="#"
                        class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl px-6 py-3 text-sm font-semibold text-black bg-white hover:bg-white/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white"
                    >AANMELDEN</a>
                    <Link
                        :href="history()"
                        class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl px-6 py-3 text-sm font-semibold border border-white/30 text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white"
                    >Bekijk vorige edities</Link>
                </div>

            </div>

            <!-- Right column: media card -->
            <div class="relative">
                <div class="relative rounded-2xl bg-white/5 p-6 ring-1 ring-white/10 backdrop-blur-md text-white/95">
                    <div class="inline-flex items-center gap-6">
                        <div class="text-4xl md:text-5xl font-extrabold leading-none">25&nbsp;juni</div>
                        <div class="flex flex-col text-sm md:text-base font-medium">
                            <div class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.25a9.75 9.75 0 1 0 0 19.5 9.75 9.75 0 0 0 0-19.5Zm.75 5.25a.75.75 0 0 0-1.5 0v5.25c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V7.5Z"/></svg>
                                17:00
                            </div>
                            <div class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.25c-3.728 0-6.75 3.022-6.75 6.75 0 5.19 6.75 12.75 6.75 12.75s6.75-7.56 6.75-12.75c0-3.728-3.022-6.75-6.75-6.75Zm0 9a2.25 2.25 0 1 1 0-4.5 2.25 2.25 0 0 1 0 4.5Z"/></svg>
                                Slingerweg 7D, Breda
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div class="flex min-h-screen flex-col items-stretch p-6 text-[#1b1b18] lg:p-8">

        <div class="hidden h-14.5 lg:block"></div>
    </div>
    <AppFooter/>
</template>
