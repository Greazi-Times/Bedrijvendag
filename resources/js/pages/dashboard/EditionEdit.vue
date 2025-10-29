<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
const thumbnailInput = ref<HTMLInputElement | null>(null);
import axios from 'axios';

const props = defineProps({
    edition: {
        type: Object,
        required: true,
    },
});

interface EditionForm {
    name: string;
    description: string;
    date: string;
    images: string;
    thumbnail: File | null;
}

const preview = ref(props.edition.thumbnail);

const form = useForm<EditionForm>({
    name: props.edition.name,
    description: props.edition.description
        ? props.edition.description.replace(/<br\s*\/?>/g, '\n')
        : '',
    date: props.edition.date,
    images: props.edition.images,
    thumbnail: null,
});

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.thumbnail = file;
        preview.value = URL.createObjectURL(file);
    }
};

const submit = async () => {
    const fd = new FormData();
    fd.append('_method', 'PUT');

    // Compare and only send changed fields
    const original = props.edition;

    const addIfChanged = (key: keyof typeof form) => {
        const current = form[key];
        const originalValue = original[key];
        if (current !== originalValue && current !== null && current !== undefined) {
            if (key === 'date' && typeof current === 'string') {
                const d = current.split('T')[0];
                fd.append(key, d);
            } else {
                fd.append(key, current as any);
            }
        }
    };

    addIfChanged('name');
    addIfChanged('description');
    addIfChanged('date');
    addIfChanged('images');

    if (form.thumbnail) {
        fd.append('thumbnail', form.thumbnail);
    }

    console.log('Submitting FormData with changed fields only...');
    for (const [k, v] of fd.entries()) {
        console.log('FD ->', k, v);
    }

    try {
        const response = await axios.post(
            `/dashboard/edition/${props.edition.id}/update`,
            fd,
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'multipart/form-data',
                },
            },
        );

        console.log('Laravel response:', response.data);
        alert('Edition updated successfully!');
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
onBeforeUnmount(() => window.removeEventListener('keydown', handleKeydown));
</script>

<template>
    <Head title="Edit Edition" />

    <AppLayout>
        <div class="flex items-center justify-between">
            <div class="m-8 h-[calc(100vh-150px)] w-full rounded-xl border border-sidebar-border/70 bg-white p-8 shadow">
                <h1 class="mb-6 text-2xl font-semibold">Edit Edition</h1>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Name + Date -->
                    <div class="flex gap-6">
                        <div class="flex-1">
                            <label class="mb-1 block text-sm font-medium">Name</label>
                            <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-indigo-200" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>
                        <div class="w-1/3">
                            <label class="mb-1 block text-sm font-medium">Date</label>
                            <input v-model="form.date" type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-indigo-200" />
                            <p v-if="form.errors.date" class="mt-1 text-sm text-red-500">{{ form.errors.date }}</p>
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


                    <!-- Images (Google Photos link) -->
                    <div>
                        <label class="mb-1 block text-sm font-medium">Images (Google Photos Album URL)</label>
                        <input
                            v-model="form.images"
                            type="url"
                            placeholder="https://photos.google.com/album/..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:ring focus:ring-indigo-200"
                        />
                        <p v-if="form.errors.images" class="mt-1 text-sm text-red-500">{{ form.errors.images }}</p>
                    </div>

                    <!-- Thumbnail -->
                    <div class="relative w-72">
                        <label class="mb-1 block text-sm font-medium">Thumbnail</label>

                        <div class="group relative cursor-pointer overflow-hidden rounded-md border" @click="thumbnailInput?.click()">
                            <img :src="preview" alt="Current thumbnail" class="aspect-[3/4] w-full object-cover" />
                            <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition group-hover:opacity-100">
                                <span class="font-semibold text-white">Edit Thumbnail</span>
                            </div>
                        </div>

                        <input ref="thumbnailInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />
                        <p v-if="form.errors.thumbnail" class="mt-1 text-sm text-red-500">{{ form.errors.thumbnail }}</p>
                    </div>

                    <!-- Save Button -->
                    <div class="flex items-center justify-end gap-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>

                    <!-- Success message -->
                    <p v-if="form.recentlySuccessful" class="mt-4 font-medium text-green-600">Edition updated successfully!</p>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
