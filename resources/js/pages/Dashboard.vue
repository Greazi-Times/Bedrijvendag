<script setup lang="ts">
import Table from '@/components/Table.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const { companyStats, borrelCount, userCount, companies } = defineProps<{
    companyStats: { visible: number; hidden: number };
    borrelCount: number;
    userCount: { total: number; admin: number };
    companies: Array<{ id: number; name: string; visible: boolean; editions: Array<string>; sectors: Array<string> }>;
}>();

const totalEnrollments = ref<number>(borrelCount);

const fetchTotalEnrollments = async () => {
  try {
    const response = await axios.get('/borrel-enrollments/total');
    totalEnrollments.value = response.data.total;
  } catch (error) {
    console.error('Failed to fetch total enrollments:', error);
  }
};

onMounted(fetchTotalEnrollments);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const allEditions = computed(() => {
    const set = new Set<string>();
    companies?.forEach((c) => c.editions?.forEach((e) => set.add(e)));
    return Array.from(set).sort();
});

const allSectors = computed(() => {
    const set = new Set<string>();
    companies?.forEach((c) => c.sectors?.forEach((s) => set.add(s)));
    return Array.from(set).sort();
});

// Default: all sectors enabled
const selectedSectors = ref<Set<string>>(new Set());
watch(
    allSectors,
    (vals) => {
        selectedSectors.value = new Set(vals);
    },
    { immediate: true },
);

watch(
    () => companies,
    (val) => {
        console.log('📦 Companies data received:', val);
    },
    { immediate: true },
);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div
                    class="relative flex flex-col justify-center rounded-xl border border-sidebar-border/70 bg-white p-6 text-center shadow dark:border-sidebar-border dark:bg-gray-800"
                >
                    <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Geregistreerde Bedrijven</h2>
                    <div class="flex justify-center gap-4">
                        <div class="min-w-1/3 px-6 py-4 text-center">
                            <p class="text-2xl font-bold text-foreground">{{ companyStats.visible + companyStats.hidden }}</p>
                            <p class="text-sm">Totaal</p>
                        </div>
                        <div class="min-w-1/3 px-6 py-4 text-center">
                            <p class="text-2xl font-bold text-primary">{{ companyStats.visible }}</p>
                            <p class="text-sm">Zichtbaar</p>
                        </div>
                        <div class="min-w-1/3 px-6 py-4 text-center">
                            <p class="text-2xl font-bold text-foreground">{{ companyStats.hidden }}</p>
                            <p class="text-sm">Verborgen</p>
                        </div>
                    </div>
                </div>
                <div
                    class="relative flex flex-col justify-center rounded-xl border border-sidebar-border/70 bg-white p-6 text-center shadow dark:border-sidebar-border dark:bg-gray-800"
                >
                    <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Borrel Aanmeldingen</h2>
                    <div class="min-w-1/3 px-6 py-4 text-center">
                        <p class="text-2xl font-bold">{{ totalEnrollments }}</p>
                        <p class="text-sm">Totaal Ingeschreven Studenten</p>
                    </div>
                </div>
                <div
                    class="relative flex flex-col justify-center rounded-xl border border-sidebar-border/70 bg-white p-6 text-center shadow dark:border-sidebar-border dark:bg-gray-800"
                >
                    <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Aantal Gebruikers</h2>
                    <div class="min-w-1/3 px-6 py-4 text-center">
                        <div class="flex justify-center gap-4">
                            <div class="min-w-1/3 px-6 py-4 text-center">
                                <p class="text-2xl font-bold text-foreground">{{ userCount.total }}</p>
                                <p class="text-sm">Totaal</p>
                            </div>
                            <div class="min-w-1/3 px-6 py-4 text-center">
                                <p class="text-2xl font-bold text-primary">{{ userCount.admin }}</p>
                                <p class="text-sm">Admins</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <Table
                :data="companies"
                :columns="[
                    { key: 'name', label: 'Name' },
                    { key: 'editions', label: 'Editions' },
                    { key: 'sectors', label: 'Sectors' },
                    { key: 'visible', label: 'Visible' },
                ]"
                :filters="[
                    { key: 'edition', label: 'Editions', options: allEditions },
                    { key: 'sector', label: 'Sectors', options: allSectors },
                ]"
                :row-actions="[
                    { label: 'View', route: (row) => `/dashboard/company/${row.id}` },
                    { label: 'Edit', route: (row) => `/dashboard/company/${row.id}/edit` },
                    {
                        label: 'Delete',
                        route: (row) => {
                            router.visit(`/dashboard/company/${row.id}/remove`, {
                                method: 'put',
                                preserveScroll: true,
                            });
                        },
                    },
                ]"
                add-route="/dashboard/company/create"
            />
        </div>
    </AppLayout>
</template>
