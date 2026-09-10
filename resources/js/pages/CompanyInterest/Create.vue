<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowDown, CalendarDays, Check, CheckCircle2, FileText, MessagesSquare, Send, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { useTranslations } from '@/i18n';

type UpcomingEvent = {
    name: string;
    date: string | null;
};

const props = defineProps<{
    submitUrl: string;
    upcomingEvent: UpcomingEvent | null;
}>();
const { dateLocale, t } = useTranslations();

const saved = ref(false);

const form = useForm({
    company_name: '',
    website_url: '',
    contact_name: '',
    contact_email: '',
    message: '',
});

const formattedEventDate = computed(() => {
    if (!props.upcomingEvent?.date) return t('common.unknownDate');

    return new Intl.DateTimeFormat(dateLocale.value, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${props.upcomingEvent.date}T12:00:00`));
});

function submit() {
    form.post(props.submitUrl, {
        preserveScroll: true,
        onSuccess: () => {
            saved.value = true;
            form.reset();
        },
    });
}
</script>

<template>
    <Head :title="t('companyInterest.head')" />

    <AppHeader class="sticky top-0 z-50" />

    <main class="brand-hero min-h-screen">
        <section class="relative px-6 py-14 sm:py-20 lg:px-16">
            <div class="relative mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-[1.15fr_0.85fr]">
                <div class="max-w-3xl">
                    <p class="brand-eyebrow">{{ t('companyInterest.eyebrow') }}</p>
                    <h1 class="mt-5 text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">{{ t('companyInterest.title') }}</h1>
                    <p class="mt-5 max-w-2xl text-lg leading-relaxed text-muted-foreground">{{ t('companyInterest.intro') }}</p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a
                            href="#enroll"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-lg ring-1 shadow-primary/20 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                        >
                            {{ t('companyInterest.startEnrollment') }}
                            <ArrowDown class="h-4 w-4" />
                        </a>
                        <a
                            href="#how-it-works"
                            class="inline-flex items-center justify-center rounded-xl bg-white/80 px-6 py-3 text-sm font-semibold text-foreground shadow-sm ring-1 ring-border/80 transition hover:bg-accent dark:bg-white/10"
                        >
                            {{ t('companyInterest.howItWorks') }}
                        </a>
                    </div>
                </div>

                <aside class="brand-card rounded-3xl p-6 sm:p-8" aria-labelledby="next-event-title">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-secondary/15 ring-1 ring-secondary/20">
                            <CalendarDays class="h-6 w-6 text-secondary" />
                        </div>
                        <div>
                            <p id="next-event-title" class="text-sm font-semibold text-muted-foreground">{{ t('companyInterest.nextEvent') }}</p>
                            <template v-if="props.upcomingEvent">
                                <h2 class="mt-1 text-xl font-semibold text-foreground">{{ props.upcomingEvent.name }}</h2>
                                <p class="mt-2 text-sm text-muted-foreground capitalize">{{ formattedEventDate }}</p>
                            </template>
                            <template v-else>
                                <h2 class="mt-1 text-xl font-semibold text-foreground">{{ t('companyInterest.dateComingSoon') }}</h2>
                                <p class="mt-2 text-sm text-muted-foreground">{{ t('companyInterest.stillInterested') }}</p>
                            </template>
                        </div>
                    </div>
                    <ul class="mt-6 space-y-3 border-t border-border pt-6">
                        <li v-for="benefit in ['audience', 'format', 'opportunities']" :key="benefit" class="flex gap-3 text-sm leading-relaxed text-muted-foreground">
                            <Check class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                            {{ t(`companyInterest.${benefit}`) }}
                        </li>
                    </ul>
                </aside>
            </div>
        </section>

        <section id="how-it-works" class="brand-section relative border-y border-border px-6 py-14 lg:px-16">
            <div class="mx-auto max-w-7xl">
                <div class="max-w-2xl">
                    <p class="brand-eyebrow">{{ t('companyInterest.processEyebrow') }}</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight">{{ t('companyInterest.processTitle') }}</h2>
                    <p class="mt-3 leading-relaxed text-muted-foreground">{{ t('companyInterest.processIntro') }}</p>
                </div>

                <ol class="mt-9 grid gap-5 md:grid-cols-3">
                    <li v-for="(icon, index) in [Send, MessagesSquare, FileText]" :key="index" class="brand-card rounded-2xl p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/15 text-primary">
                                <component :is="icon" class="h-5 w-5" />
                            </div>
                            <span class="text-sm font-semibold text-muted-foreground">0{{ index + 1 }}</span>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold">{{ t(`companyInterest.step${index + 1}Title`) }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ t(`companyInterest.step${index + 1}Text`) }}</p>
                    </li>
                </ol>

                <div class="mt-8 grid gap-5 rounded-2xl bg-secondary/10 p-6 ring-1 ring-secondary/20 sm:grid-cols-2 sm:p-8">
                    <div class="flex gap-4">
                        <Users class="mt-0.5 h-6 w-6 shrink-0 text-secondary" />
                        <div>
                            <h3 class="font-semibold">{{ t('companyInterest.whoYouMeet') }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ t('companyInterest.whoYouMeetText') }}</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <FileText class="mt-0.5 h-6 w-6 shrink-0 text-secondary" />
                        <div>
                            <h3 class="font-semibold">{{ t('companyInterest.whatToPrepare') }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ t('companyInterest.whatToPrepareText') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="enroll" class="relative scroll-mt-24 px-6 py-14 sm:py-20 lg:px-16">
            <div class="mx-auto max-w-5xl">
                <div class="max-w-3xl">
                    <p class="brand-eyebrow">{{ t('companyInterest.formEyebrow') }}</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-foreground">{{ t('companyInterest.formTitle') }}</h2>
                    <p class="mt-3 text-base leading-relaxed text-muted-foreground">{{ t('companyInterest.formIntro') }}</p>
                </div>

                <div
                    v-if="saved"
                    class="mt-8 rounded-xl bg-emerald-500/15 p-4 text-sm text-emerald-900 ring-1 ring-emerald-500/25 dark:text-emerald-100"
                    role="status"
                    aria-live="polite"
                >
                    <div class="flex items-start gap-3">
                        <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                        <p>{{ t('companyInterest.success') }}</p>
                    </div>
                </div>

                <form class="brand-card mt-8 rounded-2xl p-6 sm:p-8" @submit.prevent="submit">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <label for="company_name" class="mb-2 block text-sm font-semibold text-foreground"
                                >{{ t('companyInterest.companyName') }} <span class="text-destructive">*</span></label
                            >
                            <input
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                autocomplete="organization"
                                :placeholder="t('companyInterest.companyPlaceholder')"
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />
                            <p v-if="form.errors.company_name" class="mt-2 text-sm text-destructive">{{ form.errors.company_name }}</p>
                        </div>

                        <div>
                            <label for="website_url" class="mb-2 block text-sm font-semibold text-foreground">{{ t('common.website') }}</label>
                            <input
                                id="website_url"
                                v-model="form.website_url"
                                type="url"
                                placeholder="https://example.com"
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />
                            <p v-if="form.errors.website_url" class="mt-2 text-sm text-destructive">{{ form.errors.website_url }}</p>
                        </div>

                        <div>
                            <label for="contact_name" class="mb-2 block text-sm font-semibold text-foreground"
                                >{{ t('companyInterest.contactPerson') }} <span class="text-destructive">*</span></label
                            >
                            <input
                                id="contact_name"
                                v-model="form.contact_name"
                                type="text"
                                autocomplete="name"
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />
                            <p v-if="form.errors.contact_name" class="mt-2 text-sm text-destructive">{{ form.errors.contact_name }}</p>
                        </div>

                        <div>
                            <label for="contact_email" class="mb-2 block text-sm font-semibold text-foreground"
                                >{{ t('companyInterest.businessEmail') }} <span class="text-destructive">*</span></label
                            >
                            <input
                                id="contact_email"
                                v-model="form.contact_email"
                                type="email"
                                autocomplete="email"
                                class="brand-input w-full rounded-xl px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />
                            <p v-if="form.errors.contact_email" class="mt-2 text-sm text-destructive">{{ form.errors.contact_email }}</p>
                        </div>

                        <div class="lg:col-span-2">
                            <label for="message" class="mb-2 block text-sm font-semibold text-foreground">{{ t('companyInterest.editionQuestion') }}</label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                rows="4"
                                :placeholder="t('companyInterest.messagePlaceholder')"
                                class="brand-input w-full rounded-xl p-4 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            ></textarea>
                            <p v-if="form.errors.message" class="mt-2 text-sm text-destructive">{{ form.errors.message }}</p>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm leading-relaxed text-muted-foreground">{{ t('companyInterest.privateLink') }}</p>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <Send class="h-4 w-4" />
                            {{ form.processing ? t('common.submitting') : t('companyInterest.sendRequest') }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <AppFooter />
</template>
