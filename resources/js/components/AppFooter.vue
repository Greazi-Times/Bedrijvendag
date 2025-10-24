<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { aboutUs, avans, companies, contact, cookiePolicy, editions, partners, privacyPolicy, termsOfService } from '@/routes';
</script>

<template>
    <footer class="bg-gray-700 text-gray-300">
        <div class="mx-auto max-w-7xl px-6 py-12">
            <div class="flex flex-col md:flex-row md:justify-between md:space-x-12">
                <!-- Left Section -->
                <div class="mb-10 md:mb-0 md:w-1/3">
                    <div class="mb-4 flex items-center space-x-3">
                        <span class="text-xl font-semibold text-white">ATIx Bedrijvendag</span>
                    </div>
                    <p class="mb-6 text-gray-400">De plek waar studenten kennis kan maken met bedrijven en zich kunnen oriënteren op het werkveld.</p>
                </div>
                <!-- Middle/Right Sections -->
                <div class="grid grid-cols-2 gap-8 md:w-2/3 md:grid-cols-4">
                    <div>
                        <h3 class="mb-4 font-semibold text-white">Evenementen</h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><Link :href="companies()" class="hover:border-b-2 hover:border-white hover:text-white">19 oktober 2025</Link></li>
                            <li><Link :href="companies()" class="hover:border-b-2 hover:border-white hover:text-white">Pricing</Link></li>
                            <li><Link :href="companies()" class="hover:border-b-2 hover:border-white hover:text-white">Integrations</Link></li>
                            <li><Link :href="companies()" class="hover:border-b-2 hover:border-white hover:text-white">Demo</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-4 font-semibold text-white">Bedrijvendag</h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><Link :href="aboutUs()" class="hover:border-b-2 hover:border-white hover:text-white">Over Ons</Link></li>
                            <li><a href="https://avans.nl" target="_blank" rel="noopener" class="hover:border-b-2 hover:border-white hover:text-white">Avans Hogeschool</a></li>
                            <li><Link :href="contact()" class="hover:border-b-2 hover:border-white hover:text-white">Contact</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-4 font-semibold text-white">Navigatie</h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><Link :href="companies()" class="hover:border-b-2 hover:border-white hover:text-white">Bedrijven</Link></li>
                            <li><Link :href="partners()" class="hover:border-b-2 hover:border-white hover:text-white">Partners</Link></li>
                            <li><Link :href="editions()" class="hover:border-b-2 hover:border-white hover:text-white">Edities</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-4 font-semibold text-white">Legal</h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><Link :href="privacyPolicy()" class="hover:border-b-2 hover:border-white hover:text-white">Privacy Policy</Link></li>
                            <li><Link :href="termsOfService()" class="hover:border-b-2 hover:border-white hover:text-white">Terms of Service</Link></li>
                            <li><Link :href="cookiePolicy()" class="hover:border-b-2 hover:border-white hover:text-white">Cookie Policy</Link></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mt-10 border-t border-gray-700 pt-6 text-center text-sm text-gray-500">
                &copy; 2025 ATIx Bedrijvendag. All rights reserved.
            </div>
        </div>
    </footer>
</template>

<style scoped></style>
