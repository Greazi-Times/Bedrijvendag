<script setup lang="ts">
import Table from '@/components/Table.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';

const { editions } = defineProps<{
  editions: Array<{
    id: number;
    name: string;
    description: string;
    date: string;
    images: string[];
    thumbnail_path: string;
  }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
  {
    title: 'Editions',
    href: '/dashboard/editions',
  },
];
</script>

<template>
  <Head title="Editions" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <Table
        :data="editions"
        :columns="[
          { key: 'name', label: 'Name' },
          { key: 'description', label: 'Description' },
          { key: 'date', label: 'Date' },
          { key: 'images', label: 'Images' },
          { key: 'thumbnail_path', label: 'Thumbnail' },
        ]"
        :row-actions="[
          { label: 'View', route: (row) => `/dashboard/edition/${row.id}` },
          { label: 'Edit', route: (row) => `/dashboard/edition/${row.id}/edit` },
        ]"
        add-route="/dashboard/edition/create"
      >
        <template #thumbnail_path="{ row }">
          <img
            v-if="row.thumbnail_path"
            :src="row.thumbnail_path"
            alt="Thumbnail"
            class="h-12 w-12 rounded object-cover border"
          />
          <span v-else class="text-gray-500">No Image</span>
        </template>

        <template #images="{ row }">
          <span class="text-sm text-gray-700">{{ row.images?.length || 0 }} images</span>
        </template>
      </Table>
    </div>
  </AppLayout>
</template>
