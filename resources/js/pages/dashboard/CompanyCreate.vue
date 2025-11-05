<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { storeCompany } from '@/routes';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';

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

const textareaRef = ref<HTMLTextAreaElement | null>(null);

const applyFormatting = (format: 'bold' | 'italic' | 'underline') => {
    if (!textareaRef.value) return;

    const textarea = textareaRef.value;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = form.description.substring(start, end);

    let formattedText = '';
    switch (format) {
        case 'bold':
            formattedText = `**${selectedText || 'bold text'}**`;
            break;
        case 'italic':
            formattedText = `*${selectedText || 'italic text'}*`;
            break;
        case 'underline':
            formattedText = `<u>${selectedText || 'underlined text'}</u>`;
            break;
    }

    form.description =
        form.description.substring(0, start) +
        formattedText +
        form.description.substring(end);

    nextTick(() => {
        const pos = start + formattedText.length;
        textarea.setSelectionRange(pos, pos);
        textarea.focus();
    });
};

const handleKeydown = (event: KeyboardEvent) => {
    if ((event.ctrlKey || event.metaKey) && !event.shiftKey) {
        if (event.key.toLowerCase() === 'b') {
            event.preventDefault();
            applyFormatting('bold');
        } else if (event.key.toLowerCase() === 'i') {
            event.preventDefault();
            applyFormatting('italic');
        }
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});

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
                        <div class="mb-2 flex gap-2">
                            <button type="button" @click="applyFormatting('bold')" class="rounded border border-gray-300 bg-gray-100 px-2 py-1 text-sm font-semibold text-gray-700 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600" title="Bold (Ctrl/Cmd+B)">B</button>
                            <button type="button" @click="applyFormatting('italic')" class="rounded border border-gray-300 bg-gray-100 px-2 py-1 text-sm font-semibold italic text-gray-700 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600" title="Italic (Ctrl/Cmd+I)">I</button>
                            <button type="button" @click="applyFormatting('underline')" class="rounded border border-gray-300 bg-gray-100 px-2 py-1 text-sm font-semibold underline text-gray-700 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600" title="Underline">&nbsp;U&nbsp;</button>
                        </div>
                        <textarea v-model="form.description" ref="textareaRef" rows="6"
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
