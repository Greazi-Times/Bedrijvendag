<script setup lang="ts"></script>

<template>
    <footer class="bg-gray-700 text-gray-300">
        <div class="mx-auto max-w-7xl px-6 py-12">
            <div class="flex flex-col md:flex-row md:justify-between md:space-x-12">
                <!-- Left Section -->
                <div class="mb-10 md:mb-0 md:w-1/3">
                    <div class="mb-4 flex items-center space-x-3">
                        <span class="text-xl font-semibold text-white">EindejaarsBBQ</span>
                    </div>
                    <p class="mb-6 text-gray-400">De beste barbecue van het schooljaar, .</p>
                    <div class="flex space-x-4">
                        <a href="#" class="hover:text-white" aria-label="Facebook">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54v-2.89h2.54v-2.205c0-2.507 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.772-1.63 1.562v1.878h2.773l-.443 2.89h-2.33v6.987C18.343 21.128 22 16.991 22 12z"
                                />
                            </svg>
                        </a>
                        <a href="#" class="hover:text-white" aria-label="Twitter">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M23 3a10.9 10.9 0 01-3.14.86 4.48 4.48 0 001.98-2.48 9.04 9.04 0 01-2.85 1.1A4.52 4.52 0 0016.67 2c-2.5 0-4.52 2.03-4.52 4.53 0 .35.04.7.11 1.03A12.94 12.94 0 013 4.15a4.52 4.52 0 001.4 6.04 4.48 4.48 0 01-2.05-.57v.06c0 2.2 1.56 4.04 3.64 4.46a4.52 4.52 0 01-2.04.08 4.52 4.52 0 004.22 3.13A9.04 9.04 0 012 19.54a12.82 12.82 0 006.92 2.03c8.3 0 12.85-6.87 12.85-12.84 0-.2 0-.42-.02-.63A9.22 9.22 0 0023 3z"
                                />
                            </svg>
                        </a>
                        <a href="#" class="hover:text-white" aria-label="Instagram">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                                <path d="M16 11.37a4 4 0 11-4.73-4.73 4 4 0 014.73 4.73z" />
                                <line x1="17.5" y1="6.5" x2="17.5" y2="6.5" />
                            </svg>
                        </a>
                        <a href="#" class="hover:text-white" aria-label="LinkedIn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-4 0v7h-4v-7a6 6 0 016-6zM2 9h4v12H2zM4 3a2 2 0 110 4 2 2 0 010-4z" />
                            </svg>
                        </a>
                    </div>
                </div>
                <!-- Middle/Right Sections -->
                <div class="grid grid-cols-2 gap-8 md:w-2/3 md:grid-cols-4">
                    <div>
                        <h3 class="mb-4 font-semibold text-white">Product</h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><a href="#" class="hover:text-white">Features</a></li>
                            <li><a href="#" class="hover:text-white">Pricing</a></li>
                            <li><a href="#" class="hover:text-white">Integrations</a></li>
                            <li><a href="#" class="hover:text-white">Demo</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-4 font-semibold text-white">Company</h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><a href="#" class="hover:text-white">About Us</a></li>
                            <li><a href="#" class="hover:text-white">Careers</a></li>
                            <li><a href="#" class="hover:text-white">Press</a></li>
                            <li><a href="#" class="hover:text-white">Blog</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-4 font-semibold text-white">Resources</h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><a href="#" class="hover:text-white">Help Center</a></li>
                            <li><a href="#" class="hover:text-white">Community</a></li>
                            <li><a href="#" class="hover:text-white">Tutorials</a></li>
                            <li><a href="#" class="hover:text-white">Webinars</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-4 font-semibold text-white">Legal</h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                            <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                            <li><a href="#" class="hover:text-white">Cookie Policy</a></li>
                            <li><a href="#" class="hover:text-white">Security</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mt-10 border-t border-gray-700 pt-6 text-center text-sm text-gray-500">&copy; 2025 EindejaarsBBQ. All rights reserved.</div>
        </div>
    </footer>
</template>

<style scoped></style>
