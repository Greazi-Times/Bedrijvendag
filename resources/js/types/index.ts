export * from './auth';
export * from './navigation';
export * from './ui';

import type { Auth } from './auth';

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    auth: Auth;
    media: {
        siteLogo: string;
    };
    borrelEnrollmentOpen: boolean;
    sidebarOpen: boolean;
    [key: string]: unknown;
};
