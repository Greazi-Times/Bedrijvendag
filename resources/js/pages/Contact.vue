<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { Head } from '@inertiajs/vue3';
import { Mail, MapPin, Phone } from 'lucide-vue-next';
</script>

<template>
    <Head title="Contact" />

    <AppHeader class="sticky top-0 z-50 bg-primary/8 backdrop-blur supports-[backdrop-filter]:bg-primary/8 dark:bg-[#161615]/80" />
    <div class="bg-white px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
        <div class="mx-auto grid max-w-6xl grid-cols-1 gap-12 lg:grid-cols-2">
            <!-- Left: Contact Info and Map -->
            <div class="flex flex-col space-y-8">
                <!-- Contact Info Card -->
                <div class="space-y-4 p-12 dark:bg-gray-800">
                    <h1 class="text-3xl font-extrabold text-foreground">Neem contact met ons op</h1>
                    <p class="font-semibold text-gray-600 dark:text-gray-400">
                        Heeft u vragen over de ATIx Bedrijvendag, wilt u meer informatie ontvangen of samenwerkingsmogelijkheden bespreken? Vul gerust
                        het formulier in en wij nemen zo snel mogelijk contact met u op.
                    </p>
                    <div class="mt-12 rounded-2xl bg-secondary/30 p-6 shadow-lg">
                        <div class="space-y-4">
                            <div class="flex items-center space-x-4">
                                <component :is="MapPin" class="size-7 text-primary" :stroke-width="1.5" />
                                <div>
                                    <div class="font-medium text-foreground">Lovensdijkstraat 61, 4818 AJ, Breda, Nederland</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <component :is="Phone" class="size-7 text-primary" :stroke-width="1.5" />
                                <div>
                                    <div class="font-medium text-foreground">088-5258600</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <component :is="Mail" class="size-7 text-primary" :stroke-width="1.5" />
                                <div>
                                    <div class="font-medium text-foreground">bedrijvendag.atix@avans.nl</div>
                                </div>
                            </div>
                        </div>
                        <!-- Map Placeholder -->
                        <!-- Disables for now, can be enabled when map is ready
                        <div class="mt-4 overflow-hidden rounded-xl border border-gray-300 shadow-lg dark:border-gray-700">
                            <div
                                class="flex h-64 w-full items-center justify-center bg-gray-100 text-xl font-semibold text-gray-500 dark:bg-gray-800"
                            >
                                Map Placeholder
                            </div>
                        </div>
                        -->
                    </div>
                </div>
            </div>
            <!-- Right: Contact Form -->
            <div class="flex w-full items-center justify-center">
                <div class="max-h-content flex w-full flex-col justify-center rounded-xl bg-accent p-8 shadow-lg dark:bg-gray-800">
                    <form action="/contact/send" method="POST">
                        <div class="mb-4">
                            <label for="name"> Naam <span class="text-red-500">*</span> </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                required
                                placeholder="Your name"
                                class="w-full rounded-md border bg-background p-2 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                            />
                        </div>

                        <div class="mb-4">
                            <label for="email"> Email <span class="text-red-500">*</span> </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                required
                                placeholder="you@example.com"
                                class="w-full rounded-md border bg-background p-2 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                            />
                        </div>

                        <div class="mb-4">
                            <label for="subject"> Subject <span class="text-red-500">*</span> </label>
                            <input
                                type="text"
                                id="subject"
                                name="subject"
                                required
                                placeholder="Subject"
                                class="w-full rounded-md border bg-background p-2 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                            />
                        </div>

                        <div class="mb-4">
                            <label for="message"> Message <span class="text-red-500">*</span> </label>
                            <textarea
                                id="message"
                                name="message"
                                required
                                placeholder="Your message"
                                rows="10"
                                class="w-full rounded-md border bg-background p-2 focus:border-primary focus:ring-2 focus:ring-primary focus:outline-none"
                            ></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-md bg-primary p-2 text-lg font-bold text-white">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <AppFooter />
</template>
