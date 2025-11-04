<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { storeCompany } from '@/routes';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

const form = useForm({
    name: '',
    description: '',
    href: '',
    editions: [] as string[],
    sectors: [] as string[],
    logo: null as File | null,
});

const props = defineProps<{
    editionsOptions: string[];
    sectorOptions: string[];
}>();

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.logo = target.files[0];
    }
};

const submit = () => {
    form.post(storeCompany.post().url, {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Add Company" />
    <AppLayout>
        <div class="flex h-[calc(100vh-84px)] items-center justify-center">
            <div class="w-1/2 rounded-xl border border-sidebar-border/70 bg-white p-8 shadow dark:border-sidebar-border dark:bg-gray-800">
                <h1 class="mb-6 text-2xl font-semibold text-gray-900 dark:text-gray-100">Add New Company</h1>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium">Name</label>
                        <input v-model="form.name" type="text" required
                               class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/50" />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium">Description</label>
                        <textarea v-model="form.description" rows="6"
                                  class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/50"
                                  placeholder="Company description with multiple lines..."></textarea>
                    </div>

                    <!-- Website -->
                    <div>
                        <label class="block text-sm font-medium">Website</label>
                        <input v-model="form.href" type="url"
                               class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/50"
                               placeholder="https://example.com" />
                    </div>

                    <!-- Editions -->
                    <div>
                        <label class="block text-sm font-medium">Editions</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <label v-for="ed in props.editionsOptions" :key="ed" class="flex items-center gap-2">
                                <input type="checkbox" :value="ed" v-model="form.editions" />
                                {{ ed }}
                            </label>
                        </div>
                    </div>

                    <!-- Sectors -->
                    <div>
                        <label class="block text-sm font-medium">Sectors</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <label v-for="s in props.sectorOptions" :key="s" class="flex items-center gap-2 capitalize">
                                <input type="checkbox" :value="s" v-model="form.sectors" />
                                {{ s }}
                            </label>
                        </div>
                    </div>

                    <!-- Logo -->
                    <div>
                        <label class="block text-sm font-medium">Company Logo</label>
                        <input type="file" @change="onFileChange" accept="image/*"
                               class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/50" />
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing">Save Company</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
