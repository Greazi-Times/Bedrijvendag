<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';

// ✅ Store props in a variable (fixes runtime error)
const props = defineProps<{
    title?: string;
    addRoute?: string;
    data: Array<any>;
    columns: Array<{ key: string; label: string }>;
    filters?: Array<{ key: string; label: string; options: Array<string> }>;
    searchPlaceholder?: string;
    addLabel?: string;
    rowActions?: Array<{ label: string; route: (row: any) => string }>; // ✅ new
}>();

const emit = defineEmits(['search', 'filter-change', 'sort-change']);

// Internal state
const search = ref('');
const rowsPerPage = ref(5);
const page = ref(1);
const sortBy = ref<{ key: string; dir: 'asc' | 'desc' }>({ key: '', dir: 'asc' });
const activeFilters = ref<Record<string, string | 'all'>>({});
const openMenuId = ref<number | null>(null); // ✅ menu open/close state

// --- filtering & sorting ---
const filtered = computed(() => {
    let list = [...(props.data ?? [])]; // ✅ use props.data

    // search
    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter((row) =>
            Object.values(row).some((val) => String(val).toLowerCase().includes(q)),
        );
    }

    // filters
    for (const [key, val] of Object.entries(activeFilters.value)) {
        if (val !== 'all' && val) {
            list = list.filter((row) => {
                const field = row[key];
                return Array.isArray(field)
                    ? field.includes(val)
                    : String(field) === String(val);
            });
        }
    }

    // sort
    if (sortBy.value.key) {
        const dir = sortBy.value.dir === 'asc' ? 1 : -1;
        const k = sortBy.value.key;
        list.sort((a, b) => {
            const av = a[k] ?? '';
            const bv = b[k] ?? '';
            return av > bv ? dir : av < bv ? -dir : 0;
        });
    }

    return list;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / rowsPerPage.value)));
const paged = computed(() => {
    const start = (page.value - 1) * rowsPerPage.value;
    return filtered.value.slice(start, start + rowsPerPage.value);
});

watch([search, activeFilters, rowsPerPage], () => {
    page.value = 1;
});

function toggleSort(key: string) {
    if (sortBy.value.key === key) {
        sortBy.value.dir = sortBy.value.dir === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = { key, dir: 'asc' };
    }
    emit('sort-change', sortBy.value);
}

// ✅ Add menu logic
function toggleMenu(id: number) {
    openMenuId.value = openMenuId.value === id ? null : id;
}

function doAction(action: { label: string; route: (row: any) => string }, row: any) {
    openMenuId.value = null;
    router.visit(action.route(row));
}

// ✅ Fix add button navigation
function goToAdd() {
    if (props.addRoute) router.visit(props.addRoute);
}
</script>

<template>
    <div class="flex flex-col h-full rounded-xl border border-sidebar-border/70 bg-white p-6 dark:border-sidebar-border dark:bg-gray-800">
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="sm:w-1/2">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="searchPlaceholder ?? 'Search...'"
                    class="w-full rounded-md border border-sidebar-border/70 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:ring-2 focus:ring-primary/50 dark:border-sidebar-border dark:bg-gray-900"
                />
            </div>

            <!-- Filters & Add -->
            <div class="flex flex-wrap items-center gap-2">
                <div v-for="f in filters" :key="f.key">
                    <select
                        v-model="activeFilters[f.key]"
                        class="rounded-md border border-sidebar-border/70 bg-white px-2 py-2 text-sm dark:border-sidebar-border dark:bg-gray-900"
                    >
                        <option value="all">All {{ f.label.toLowerCase() }}</option>
                        <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                </div>

                <Button class="ml-1" @click="goToAdd">{{ addLabel ?? 'Add' }}</Button>
            </div>
        </div>

        <!-- Table -->
        <div class="flex-1 mt-4 overflow-x-auto rounded-xl border border-sidebar-border/70">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase tracking-wide"
                        @click="toggleSort(col.key)"
                    >
                        {{ col.label }}
                        <span v-if="sortBy.key === col.key">
                                {{ sortBy.dir === 'asc' ? '↑' : '↓' }}
                            </span>
                    </th>
                    <th v-if="props.rowActions" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="row in paged" :key="row.id" class="hover:bg-muted/30">
                    <td v-for="col in columns" :key="col.key" class="px-4 py-3">
                        <slot :name="col.key" :row="row">
                            {{ Array.isArray(row[col.key]) ? row[col.key].join(', ') : row[col.key] }}
                        </slot>
                    </td>

                    <!-- ✅ Action menu column -->
                    <td v-if="props.rowActions" class="px-4 py-3 text-right">
                        <div class="relative inline-block text-left">
                            <button
                                class="inline-flex w-8 h-8 items-center justify-center rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
                                @click="toggleMenu(row.id)"
                            >
                                ⋮
                            </button>

                            <div
                                v-if="openMenuId === row.id"
                                class="absolute right-0 z-10 mt-2 w-36 origin-top-right rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                            >
                                <button
                                    v-for="action in props.rowActions"
                                    :key="action.label"
                                    class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                                    @click="doAction(action, row)"
                                >
                                    {{ action.label }}
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-sidebar-border/70 pt-4">
            <div class="text-sm text-muted-foreground">
                Total {{ filtered.length }} entries
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm">Rows per page:</label>
                <select
                    v-model.number="rowsPerPage"
                    class="rounded-md border border-sidebar-border/70 bg-white px-2 py-1 text-sm dark:border-sidebar-border dark:bg-gray-900"
                >
                    <option :value="5">5</option>
                    <option :value="10">10</option>
                    <option :value="15">15</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" :disabled="page === 1" @click="page--">Previous</Button>
                <div class="text-sm">Page {{ page }} / {{ totalPages }}</div>
                <Button variant="outline" size="sm" :disabled="page === totalPages" @click="page++">Next</Button>
            </div>
        </div>
    </div>
</template>
