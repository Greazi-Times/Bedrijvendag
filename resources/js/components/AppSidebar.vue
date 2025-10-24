<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { createCompany, dashboard, home } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Github, LayoutGrid, Book, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const companyItems: NavItem[] = [
    {
        title: 'Add Company',
        href: createCompany(),
        icon: LayoutGrid,
    },
    {
        title: 'Edit Company',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const editionItems: NavItem[] = [
  {
    title: 'Manage Editions',
    href: '/dashboard/editions',
    icon: Book,
  },
];

const userItems: NavItem[] = [
  {
    title: 'Manage Users',
    href: '/dashboard/users',
    icon: Users,
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
            <NavMain :items="companyItems" label="Companies" />
            <NavMain :items="editionItems" label="Editions" />
            <NavMain :items="userItems" label="Users" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
