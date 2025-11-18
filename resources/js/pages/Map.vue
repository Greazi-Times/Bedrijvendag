<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { Head } from '@inertiajs/vue3';

const { stands } = defineProps<{
    stands: Array<{
        id: number;
        display_id: string | null;
        company_name: string | null;
    }>;
}>();
</script>

<template>
    <Head title="Plattengrond" />

    <AppHeader class="sticky top-0 z-50 bg-primary/8 backdrop-blur supports-[backdrop-filter]:bg-primary/8 dark:bg-[#161615]/80" />

    <section class="container mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-4 gap-8 items-stretch">
        <!-- Map -->
        <div class="lg:col-span-3 flex justify-center items-center border rounded-lg p-4 bg-white dark:bg-neutral-900 shadow-sm h-full">
            <img src="/images/map-3.jpg" alt="Plattegrond" class="max-w-full h-auto rounded-md aspect-[4/3] object-contain" />
        </div>

        <!-- Table -->
        <div class="lg:col-span-1 border rounded-lg p-4 bg-white dark:bg-neutral-900 shadow-sm overflow-y-auto max-h-[90vh] h-full flex flex-col">
            <h2 class="text-lg font-semibold mb-4">Bedrijven &amp; Stands</h2>
            <div class="flex-1 overflow-y-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th class="p-2">ID</th>
                            <th class="p-2">Bedrijf</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="stand in stands" :key="stand.id" class="border-b hover:bg-gray-50 dark:hover:bg-neutral-800">
                            <td class="p-2 font-medium">{{ stand.display_id }}</td>
                            <td class="p-2" :class="(stand.company_name && stand.company_name.trim()) ? '' : 'text-secondary'">
                              {{ (stand.company_name && stand.company_name.trim()) ? stand.company_name : 'Nader te bepalen' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <AppFooter />
</template>
