<script setup lang="ts">
</script>

<template>
    <footer class="dark:bg-blacksection bg-gray-50">
        <div class="mx-auto max-w-[80%] px-6 lg:px-8">
            <!-- Top Section -->
            <div class="py-20">
                <div class="grid grid-cols-1 gap-12 lg:grid-cols-4">
                    <!-- Logo + Description -->
                    <div>
                        <a href="/" class="flex items-center gap-3">
                            <img src="/favicon.svg" class="h-10 dark:hidden" alt="Logo" />
                            <img src="/favicon.svg" class="hidden h-10 dark:block" alt="Logo" />
                            <span class="text-xl font-semibold text-gray-900 dark:text-white"> ATIx Bedrijvendag </span>
                        </a>

                        <p class="mt-6 text-gray-600 dark:text-gray-400">De bedrijvendag van Avans ATIx opleidingen waar studenten en bedrijven elkaar ontmoeten.</p>

                        <div class="mt-6 flex gap-4">
                            <a href="#" class="text-gray-400 transition hover:text-primary">
                                <i class="lucide lucide-facebook h-5 w-5"></i>
                            </a>
                            <a href="#" class="text-gray-400 transition hover:text-primary">
                                <i class="lucide lucide-twitter h-5 w-5"></i>
                            </a>
                            <a href="#" class="text-gray-400 transition hover:text-primary">
                                <i class="lucide lucide-linkedin h-5 w-5"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Pages -->
                    <div>
                        <h4 class="mb-6 text-2xl font-semibold text-foreground">Pagina’s</h4>
                        <ul class="space-y-3 text-gray-600 dark:text-gray-400">
                            <li><a href="/" class="hover:text-primary">Home</a></li>
                            <li><a href="/edities" class="hover:text-primary">Evenementen</a></li>
                            <li><a href="/bedrijven" class="hover:text-primary">Bedrijven</a></li>
                            <li><a href="/contact" class="hover:text-primary">Contact</a></li>
                        </ul>
                    </div>

                    <!-- Info -->
                    <div>
                        <h4 class="mb-6 text-2xl font-semibold text-foreground">Informatie</h4>
                        <ul class="space-y-3 text-gray-600 dark:text-gray-400">
                            <li><a href="/edities" class="hover:text-primary">Programma</a></li>
                            <li><a href="/contact" class="hover:text-primary">Locatie</a></li>
                            <li><a href="/partners" class="hover:text-primary">Partners</a></li>
                            <li><a href="/dashboard" class="hover:text-primary">Dashboard</a></li>
                        </ul>
                    </div>

                    <!-- Newsletter -->
                    <div>
                        <h4 class="mb-6 text-2xl font-semibold text-foreground">Nieuwsbrief</h4>

                        <p class="mb-4 text-gray-600 dark:text-gray-400">Ontvang updates over toekomstige edities.</p>

                        <form class="relative">
                            <input
                                type="email"
                                placeholder="Email adres"
                                class="w-full rounded-full border border-gray-300 py-3 pr-14 pl-6 text-sm focus:border-primary focus:outline-none dark:border-gray-700 dark:bg-black dark:text-white"
                                required
                            />
                            <button type="submit" class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full bg-primary p-2 text-white transition hover:opacity-90">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M13 6l6 6-6 6" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="border-t border-gray-200 py-8 dark:border-gray-800">
                <div class="flex flex-col items-center justify-between gap-4 lg:flex-row">
                    <ul class="flex gap-6 text-sm text-gray-600 dark:text-gray-400">
                        <li><a href="/privacy-policy" class="hover:text-primary">Privacy Policy</a></li>
                        <li><a href="/terms-of-service" class="hover:text-primary">Terms of Service</a></li>
                        <li><a href="/cookie-policy" class="hover:text-primary">Cookie Policy</a></li>
                    </ul>

                    <p class="text-sm text-gray-500 dark:text-gray-400">© {{ new Date().getFullYear() }} ATIx Bedrijvendag. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
</template>

<style scoped></style>
