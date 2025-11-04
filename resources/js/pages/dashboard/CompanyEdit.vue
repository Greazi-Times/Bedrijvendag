<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
const logoInput = ref<HTMLInputElement | null>(null);
import axios from 'axios';
import { watch } from 'vue';

const props = defineProps({
    company: {
        type: Object,
        required: true,
    },
    editions: {
        type: Array,
        required: true,
    },
    sectors: {
        type: Array,
        required: true,
    },
});

interface CompanyForm {
    name: string;
    description: string;
    href: string;
    visible: boolean;
    editions: string[];
    sectors: string[];
    logo: File | null;
}

const preview = ref(props.company.logo);

const form = useForm<CompanyForm>({
    name: props.company.name,
    description: props.company.description
        ? props.company.description.replace(/<br\s*\/?>/g, '\n')
        : '',
    href: props.company.href,
    visible: props.company.visible,
    editions: Array.isArray(props.company.editions) ? [...props.company.editions] : [],
    sectors: Array.isArray(props.company.sectors) ? [...props.company.sectors] : [],
    logo: null,
});

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo = file;
        preview.value = URL.createObjectURL(file);
    }
};

const submit = async () => {
    const fd = new FormData();
    fd.append('_method', 'PUT');

    // Compare and only send changed fields
    const original = props.company;

    const addIfChanged = (key: keyof typeof form) => {
        const current = form[key];
        const originalValue = original[key];
        if (key === 'editions' || key === 'sectors') {
            // Compare arrays
            if (
                Array.isArray(current) &&
                Array.isArray(originalValue) &&
                (current.length !== originalValue.length ||
                    current.some((val, index) => val !== originalValue[index]))
            ) {
                // Append each value as key[] so Laravel interprets as array
                current.forEach((value) => {
                    fd.append(`${key}[]`, value);
                });
            }
        } else if (current !== originalValue && current !== null && current !== undefined) {
            fd.append(key, current as any);
        }
    };

    addIfChanged('name');
    addIfChanged('description');
    addIfChanged('href');
    addIfChanged('visible');
    addIfChanged('editions');
    addIfChanged('sectors');

    if (form.logo) {
        fd.append('logo', form.logo);
    }

    for (const [k, v] of fd.entries()) {

    }

    try {
        const response = await axios.post(
            `/dashboard/company/${props.company.id}/update`,
            fd,
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'multipart/form-data',
                },
            },
        );

        alert('Company updated successfully!');
    } catch (err: any) {
        console.error('Update failed:', err?.response?.data || err);
        alert('Update failed. Check console / laravel.log.');
    }
};

const textareaRef = ref<HTMLTextAreaElement | null>(null);

const applyFormatting = (type: 'bold' | 'italic' | 'underline') => {
    const textarea = textareaRef.value;
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selected = form.description.substring(start, end);

    let wrapped = selected;
    if (type === 'bold') wrapped = `**${selected || 'bold text'}**`;
    else if (type === 'italic') wrapped = `*${selected || 'italic text'}*`;
    else if (type === 'underline') wrapped = `<u>${selected || 'underline text'}</u>`;

    form.description =
        form.description.substring(0, start) + wrapped + form.description.substring(end);

    nextTick(() => {
        textarea.focus();
        if (type === 'underline') {
            textarea.setSelectionRange(start + 3, start + wrapped.length - 4);
        } else if (type === 'bold') {
            textarea.setSelectionRange(start + 2, start + wrapped.length - 2);
        } else if (type === 'italic') {
            textarea.setSelectionRange(start + 1, start + wrapped.length - 1);
        }
    });
};

const handleKeydown = (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'b') {
        e.preventDefault();
        applyFormatting('bold');
    }
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'i') {
        e.preventDefault();
        applyFormatting('italic');
    }
};

onMounted(() => window.addEventListener('keydown', handleKeydown));

// Sync form data whenever company prop changes (initial load or reload)
watch(
    () => props.company,
    (company) => {
        if (!company) return;

        form.name = company.name || '';
        form.description = company.description
            ? company.description.replace(/<br\s*\/?>/g, '\n')
            : '';
        form.href = company.href || '';
        form.visible = !!company.visible;
        form.editions = Array.isArray(company.editions) ? [...company.editions] : [];
        form.sectors = Array.isArray(company.sectors) ? [...company.sectors] : [];
        preview.value = company.logo || '';
    },
    { immediate: true } // runs on component mount too
);
onBeforeUnmount(() => window.removeEventListener('keydown', handleKeydown));
</script>

<template>
    <Head title="Edit Company" />

    <AppLayout>
        <div class="flex items-center justify-between">
            <div class="m-8 h-[calc(100vh-150px)] w-full rounded-xl border border-sidebar-border/70 bg-white p-8 shadow">
                <h1 class="mb-6 text-2xl font-semibold">Edit Company</h1>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Name + Href + Visible -->
                    <div class="flex gap-6">
                        <div class="flex-1">
                            <label class="mb-1 block text-sm font-medium">Name</label>
                            <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-indigo-200" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>
                        <div class="w-1/3">
                            <label class="mb-1 block text-sm font-medium">Website URL</label>
                            <input v-model="form.href" type="url" class="w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-indigo-200" />
                            <p v-if="form.errors.href" class="mt-1 text-sm text-red-500">{{ form.errors.href }}</p>
                        </div>
                        <div class="flex items-center pt-6">
                            <label class="inline-flex items-center space-x-2">
                                <input type="checkbox" v-model="form.visible" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring focus:ring-indigo-200" />
                                <span class="text-sm font-medium text-gray-700">Visible</span>
                            </label>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="mb-1 block text-sm font-medium">Description</label>
                        <textarea
                            ref="textareaRef"
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-indigo-200"
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">{{ form.errors.description }}</p>
                    </div>

                    <!-- Editions -->
                    <div>
                        <label class="mb-1 block text-sm font-medium">Editions</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <label v-for="ed in props.editions" :key="ed" class="flex items-center gap-2">
                                <input type="checkbox" :value="ed" v-model="form.editions" />
                                {{ ed }}
                            </label>
                        </div>
                        <p v-if="form.errors.editions" class="mt-1 text-sm text-red-500">{{ form.errors.editions }}</p>
                    </div>

                    <!-- Sectors -->
                    <div>
                        <label class="mb-1 block text-sm font-medium">Sectors</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <label v-for="s in props.sectors" :key="s" class="flex items-center gap-2 capitalize">
                                <input type="checkbox" :value="s" v-model="form.sectors" />
                                {{ s }}
                            </label>
                        </div>
                        <p v-if="form.errors.sectors" class="mt-1 text-sm text-red-500">{{ form.errors.sectors }}</p>
                    </div>

                    <!-- Logo -->
                    <div class="relative w-72">
                        <label class="mb-1 block text-sm font-medium">Logo</label>

                        <div class="group relative cursor-pointer overflow-hidden rounded-md border" @click="logoInput?.click()">
                            <img :src="preview" alt="Current logo" class="aspect-[3/4] w-full object-cover" />
                            <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition group-hover:opacity-100">
                                <span class="font-semibold text-white">Edit Logo</span>
                            </div>
                        </div>

                        <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />
                        <p v-if="form.errors.logo" class="mt-1 text-sm text-red-500">{{ form.errors.logo }}</p>
                    </div>

                    <!-- Save and Delete Buttons -->
                    <div class="flex items-center justify-end gap-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            @click="() => {
                                if (confirm('Are you sure you want to delete this company? This action cannot be undone.')) {
                                    router.visit(`/dashboard/company/${props.company.id}/remove`, {
                                        method: 'put',
                                        preserveScroll: true,
                                    });
                                }
                            }"
                        >
                            Delete
                        </Button>
                    </div>

                    <!-- Success message -->
                    <p v-if="form.recentlySuccessful" class="mt-4 font-medium text-green-600">Company updated successfully!</p>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
