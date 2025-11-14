<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard, dashEditions, home } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Book, Github, LayoutGrid, Paperclip, Pin, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const editionItems: NavItem[] = [
    {
        title: 'Manage Editions',
        href: dashEditions(),
        icon: Book,
    },
];

const standItems: NavItem[] = [
    {
        title: 'Stands',
        href: '/dashboard/stands',
        icon: Pin,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/Greazi-Times/EindejaarsBBQ',
        icon: Github,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="home()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="space-y-6">
            <NavMain :items="mainNavItems" label="Dashboard" />
            <NavMain :items="editionItems" label="Editions" />
            <NavMain :items="standItems" label="Stands" />
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <a href="/dashboard/stands/pdf" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2">
                            <Paperclip class="w-4 h-4" />
                            <span>Create PDF</span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
