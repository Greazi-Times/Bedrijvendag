import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    role: string;
}

export interface Partner {
    name: string;
    logo: string;
    href: string;
    visible: boolean;
}

export interface Company {
    name: string;
    description: string[];
    educations: string[];
    logo: string;
    href: string;
    visible: boolean;
}

export interface Edition {
    name: string;
    description: string;
    date: string;
    images: string;
    thumbnail: string;
}

export interface PreviousEvent {
    title: string;
    description: string;
    date: string;
    image: string;
    href: string;
}

export interface OurValues {
    icon: LucideIcon;
    title: string;
    description: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
