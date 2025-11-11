<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { aboutUs, companies, contact, cookiePolicy, dashboard, editions, login, partners, privacyPolicy, termsOfService } from '@/routes';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

const page = usePage();

// Normalize editions coming from shared props or page props.
const allEditions = computed(() => {
    // Try multiple locations where the data could be provided
    // e.g., via Inertia::share(['editions' => ...]) or directly from the page.
    // Fallback to empty array if not present.
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const p: any = page.props ?? {};
    const raw = p.editions ?? (p.shared && p.shared.editions) ?? (p.layout && p.layout.editions) ?? [];

    if (!Array.isArray(raw)) return [];

    // Normalize to array of objects with { id?, name }
    if (raw.length > 0) {
        if (typeof raw[0] === 'string') {
            return raw.map((name: string, idx: number) => ({ id: idx, name }));
        }
        if (typeof raw[0] === 'object') {
            // Ensure each item at least has a name
            return raw
                .filter((it: unknown) => it && typeof (it as any).name === 'string')
                .map((it: any) => ({ id: it.id ?? it.value ?? it.name, name: it.name }));
        }
    }
    return [];
});

// Show the last 4 items (assuming the incoming order is from oldest->newest)
const recentEditions = computed(() => {
    const list = allEditions.value;
    return list.slice(-4).reverse();
});
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
                        <ul v-if="recentEditions.length" class="space-y-2 text-sm text-gray-400">
                            <li v-for="edition in recentEditions" :key="edition.id">
                                <Link :href="editions()" class="hover:border-b-2 hover:border-white hover:text-white">
                                    {{ edition.name }}
                                </Link>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-400/70">Nog geen edities beschikbaar.</p>
                    </div>
                    <div>
                        <h3 class="mb-4 font-semibold text-white">Bedrijvendag</h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><Link :href="aboutUs()" class="hover:border-b-2 hover:border-white hover:text-white">Over Ons</Link></li>
                            <li>
                                <a href="https://avans.nl" target="_blank" rel="noopener" class="hover:border-b-2 hover:border-white hover:text-white"
                                    >Avans Hogeschool</a
                                >
                            </li>
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
                            <li>
                                <Link :href="termsOfService()" class="hover:border-b-2 hover:border-white hover:text-white">Terms of Service</Link>
                            </li>
                            <li><Link :href="cookiePolicy()" class="hover:border-b-2 hover:border-white hover:text-white">Cookie Policy</Link></li>
                        </ul>
                    </div>
                </div>
                <div>
                    <Link
                        class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground hover:bg-primary/75"
                        :href="login()"
                    >
                        Dashboard
                    </Link>
                </div>
            </div>
            <div class="mt-10 border-t border-gray-700 pt-6 text-center text-gray-400">
                &copy; 2025 Geazi. All rights reserved. Made with ♥ by Greazi.
            </div>
        </div>
    </footer>
</template>

<style scoped></style>
