<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const { stands, companies } = defineProps<{
    stands: Array<{
        id: number;
        display_id?: string | number;
        company_id: number | null;
        company_name: string | null;
    }>;
    companies: Array<{
        id: number;
        name: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Stands',
        href: '/dashboard/stands',
    },
];

// State to track which stand is being edited
const editingStandId = ref<number | null>(null);
// Search input for company names
const companySearch = ref('');
// Selected company id for editing stand
const selectedCompanyId = ref<number | null>(null);

// Filtered companies based on search input
const filteredCompanies = computed(() => {
    if (!companySearch.value) {
        return companies;
    }
    const searchLower = companySearch.value.toLowerCase();
    return companies.filter((c) => c.name.toLowerCase().includes(searchLower));
});

// When editingStandId changes, reset search and selectedCompanyId
watch(editingStandId, (newId) => {
    if (newId !== null) {
        const stand = stands.find((s) => s.id === newId);
        companySearch.value = stand?.company_name ?? '';
        selectedCompanyId.value = stand?.company_id ?? null;
    } else {
        companySearch.value = '';
        selectedCompanyId.value = null;
    }
});

function startEditing(standId: number) {
    editingStandId.value = standId;
}

function cancelEditing() {
    editingStandId.value = null;
}

function isCompanyAssigned(companyId: number) {
    return stands.some((s) => s.company_id === companyId);
}

function selectCompany(companyId: number | null, companyName: string | null) {
    selectedCompanyId.value = companyId;
    companySearch.value = companyName ?? '';
    if (editingStandId.value !== null) {
        const standId = editingStandId.value;
        router.patch(
            `/dashboard/stands/${standId}`,
            { company_id: companyId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Only remove the company from other stands when assigning a real company
                    if (companyId !== null) {
                        stands.forEach((s) => {
                            if (s.company_id === companyId && s.id !== standId) {
                                s.company_id = null;
                                s.company_name = null;
                            }
                        });
                    }
                    const stand = stands.find((s) => s.id === standId);
                    if (stand) {
                        if (companyId === null) {
                            stand.company_id = null;
                            stand.company_name = null;
                        } else {
                            const company = companies.find((c) => c.id === companyId);
                            if (company) {
                                stand.company_id = company.id;
                                stand.company_name = company.name;
                            }
                        }
                    }
                    editingStandId.value = null;
                    companySearch.value = '';
                },
                onFinish: () => {
                    // prevent default Inertia success message
                },
                onError: () => {
                    alert('Something went wrong while updating the stand.');
                },
            },
        );
    }
}
</script>

<template>
    <Head title="Stands" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 gap-4 overflow-x-auto rounded-xl p-4">
            <div class="sticky top-0 w-3/4 overflow-auto">
                <img src="/images/map-2.jpg" alt="Stands Map" class="max-h-[90vh] w-full rounded border object-contain" />
            </div>
            <div class="max-h-[90vh] w-1/4 overflow-y-scroll rounded border">
                <table class="min-w-full divide-y divide-gray-200 rounded-lg border border-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Stand ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Company</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="stand in stands" :key="stand.id" class="cursor-pointer hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                <span v-if="stand.id >= 100" class="font-semibold text-green-600">
                                    {{ stand.display_id }}
                                </span>
                                <span v-else class="text-gray-900">
                                    {{ stand.display_id }}
                                </span>
                            </td>
                            <td class="relative px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                <div v-if="editingStandId === stand.id" class="relative">
                                    <input
                                        type="text"
                                        v-model="companySearch"
                                        placeholder="Search company..."
                                        class="w-full rounded border border-gray-300 px-2 py-1"
                                        @keydown.escape.prevent="cancelEditing"
                                        @keydown.enter.prevent="
                                            () => {
                                                if (filteredCompanies.length > 0) selectCompany(filteredCompanies[0].id, filteredCompanies[0].name);
                                            }
                                        "
                                        autofocus
                                    />
                                    <ul
                                        v-if="filteredCompanies.length > 0"
                                        class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded border border-gray-300 bg-white shadow-lg"
                                    >
                                        <li
                                            :class="[
                                                'cursor-pointer px-2 py-1 hover:bg-blue-500 hover:text-white',
                                                selectedCompanyId === null ? 'font-medium text-secondary' : 'text-black',
                                            ]"
                                            @click.prevent="selectCompany(null, null)"
                                        >
                                            No Company
                                        </li>

                                        <li
                                            v-for="company in selectedCompanyId !== null ? companies : filteredCompanies"
                                            :key="company.id"
                                            :class="[
                                                'cursor-pointer px-2 py-1 hover:bg-blue-500 hover:text-white',
                                                selectedCompanyId === company.id
                                                    ? 'font-medium text-secondary'
                                                    : isCompanyAssigned(company.id)
                                                      ? 'text-black'
                                                      : 'font-medium text-primary',
                                            ]"
                                            @click.prevent="selectCompany(company.id, company.name)"
                                        >
                                            {{ company.name }}
                                        </li>
                                    </ul>
                                    <p
                                        v-else
                                        class="absolute z-10 mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1 text-sm text-gray-500"
                                    >
                                        No companies found.
                                    </p>
                                </div>
                                <div v-else class="truncate" @click="startEditing(stand.id)" title="Click to edit company">
                                    <span v-if="!stand.company_name" class="font-bold text-primary">No Company</span>
                                    <span v-else>{{ stand.company_name }}</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
