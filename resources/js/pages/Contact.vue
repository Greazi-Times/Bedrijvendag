<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { history } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppFooter from '@/components/AppFooter.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'History',
        href: history().url,
    },
];
</script>

<template>
    <Head title="Contact" />

    <AppHeader class="sticky top-0 z-50 bg-primary/8 backdrop-blur supports-[backdrop-filter]:bg-primary/8 dark:bg-[#161615]/80" />
    <header class="text-foreground p-6">CONTACT PAGE</header>

    <AppFooter />

</template>
