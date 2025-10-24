<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { storeEdition } from '@/routes';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    description: '',
    date: '',
    images: '',
    thumbnail: null as File | null,
});

const onThumbnailChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.thumbnail = target.files[0];
    }
};

const submit = () => {
    form.post(storeEdition.post().url, {
        forceFormData: true,
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Add Edition" />

        <div class="mx-auto max-w-3xl py-10">
            <div class="mb-4 rounded bg-white px-8 pt-6 pb-8 shadow-md">
                <h1 class="mb-6 text-2xl font-bold">Create Edition</h1>
                <form @submit.prevent="submit" enctype="multipart/form-data" novalidate>
                    <div class="mb-4">
                        <label for="name" class="mb-2 block font-bold text-gray-700">Name</label>
                        <input
                            id="name"
                            type="text"
                            v-model="form.name"
                            class="focus:shadow-outline w-full appearance-none rounded border px-3 py-2 leading-tight text-gray-700 shadow focus:outline-none"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-500 italic">{{ form.errors.name }}</p>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="mb-2 block font-bold text-gray-700">Description</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="focus:shadow-outline w-full appearance-none rounded border px-3 py-2 leading-tight text-gray-700 shadow focus:outline-none"
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-xs text-red-500 italic">{{ form.errors.description }}</p>
                    </div>

                    <div class="mb-4">
                        <label for="date" class="mb-2 block font-bold text-gray-700">Date</label>
                        <input
                            id="date"
                            type="date"
                            v-model="form.date"
                            class="focus:shadow-outline w-full appearance-none rounded border px-3 py-2 leading-tight text-gray-700 shadow focus:outline-none"
                        />
                        <p v-if="form.errors.date" class="mt-1 text-xs text-red-500 italic">{{ form.errors.date }}</p>
                    </div>

                    <div class="mb-4">
                        <label for="images" class="mb-2 block font-bold text-gray-700">Images Link</label>
                        <input
                            id="images"
                            type="text"
                            v-model="form.images"
                            class="focus:shadow-outline w-full appearance-none rounded border px-3 py-2 leading-tight text-gray-700 shadow focus:outline-none"
                        />
                        <p v-if="form.errors.images" class="mt-1 text-xs text-red-500 italic">{{ form.errors.images }}</p>
                    </div>

                    <div class="mb-6">
                        <label for="thumbnail" class="mb-2 block font-bold text-gray-700">Thumbnail</label>
                        <input
                            id="thumbnail"
                            type="file"
                            @change="onThumbnailChange"
                            class="mt-1 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/50"
                            accept="image/*"
                        />
                        <p v-if="form.errors.thumbnail" class="mt-1 text-xs text-red-500 italic">{{ form.errors.thumbnail }}</p>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing">Save Edition</Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
