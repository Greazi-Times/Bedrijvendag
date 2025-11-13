<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
const props = defineProps<{ message?: string }>();
</script>

<template>
    <AppLayout :breadcrumbs="[]">
        <Head title="Account Pending" />
        <div class="min-h-screen flex items-center justify-center">
            <div class="max-w-lg p-8 bg-white dark:bg-gray-800 rounded-lg shadow text-center">
                <h1 class="text-2xl font-semibold mb-2">Account Pending</h1>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ props.message ?? 'Your account was created successfully. An admin will review your request.' }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>

