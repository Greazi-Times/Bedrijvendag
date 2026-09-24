<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    Blocks,
    BookOpenCheck,
    BriefcaseBusiness,
    CalendarDays,
    Check,
    CheckCircle2,
    Cpu,
    ExternalLink,
    FileText,
    GraduationCap,
    Info,
    Lightbulb,
    MessagesSquare,
    Route,
    Send,
    Wrench,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { useTranslations } from '@/i18n';

type UpcomingEvent = {
    name: string;
    date: string | null;
};

type InternshipPhase = 'education' | 'orientation' | 'internship' | 'graduation';

type InternshipProgram = {
    id: string;
    icon: typeof Cpu;
    sourceUrl: string;
    phases: InternshipPhase[];
};

const internshipPrograms: InternshipProgram[] = [
    {
        id: 'mechatronics',
        icon: Blocks,
        sourceUrl: 'https://www.avans.nl/studeren/opleidingen/bachelor/mechatronica-voltijd',
        phases: ['education', 'education', 'education', 'education', 'internship', 'education', 'education', 'graduation'],
    },
    {
        id: 'mechanical',
        icon: Wrench,
        sourceUrl: 'https://www.avans.nl/studeren/opleidingen/bachelor/werktuigbouwkunde-voltijd',
        phases: ['education', 'education', 'education', 'education', 'internship', 'education', 'education', 'graduation'],
    },
    {
        id: 'electrical',
        icon: Lightbulb,
        sourceUrl: 'https://www.avans.nl/studeren/opleidingen/bachelor/elektrotechniek-voltijd',
        phases: ['education', 'education', 'education', 'education', 'internship', 'education', 'education', 'graduation'],
    },
    {
        id: 'ict',
        icon: Cpu,
        sourceUrl: 'https://www.avans.nl/studeren/opleidingen/bachelor/ict-voltijd',
        phases: ['education', 'education', 'education', 'education', 'internship', 'education', 'education', 'graduation'],
    },
    {
        id: 'businessIt',
        icon: BriefcaseBusiness,
        sourceUrl: 'https://www.avans.nl/studeren/opleidingen/bachelor/business-it-en-management-voltijd',
        phases: ['education', 'education', 'education', 'education', 'internship', 'education', 'education', 'graduation'],
    },
    {
        id: 'industrial',
        icon: Route,
        sourceUrl: 'https://www.avans.nl/studeren/opleidingen/bachelor/technische-bedrijfskunde-voltijd',
        phases: ['education', 'orientation', 'education', 'education', 'internship', 'education', 'education', 'graduation'],
    },
];

const props = defineProps<{
    submitUrl: string;
    upcomingEvent: UpcomingEvent | null;
}>();
const { dateLocale, t } = useTranslations();

const saved = ref(false);
const selectedProgramId = ref(internshipPrograms[0].id);
const selectedProgram = computed(() => internshipPrograms.find((program) => program.id === selectedProgramId.value) ?? internshipPrograms[0]);

function internshipPhaseAt(year: number, halfYear: number): InternshipPhase {
    return selectedProgram.value.phases[(year - 1) * 2 + (halfYear - 1)] ?? 'education';
}

const internshipPhaseClasses: Record<InternshipPhase, string> = {
    education: 'bg-muted/70 text-muted-foreground ring-border',
    orientation: 'bg-secondary/15 text-secondary ring-secondary/30',
    internship: 'bg-primary text-primary-foreground ring-primary shadow-sm',
    graduation: 'bg-emerald-600 text-white ring-emerald-600 dark:bg-emerald-700 dark:ring-emerald-700',
};

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
                            href="#new-education"
                            class="inline-flex items-center justify-center rounded-xl bg-white/80 px-6 py-3 text-sm font-semibold text-foreground shadow-sm ring-1 ring-border/80 transition hover:bg-accent dark:bg-white/10"
                        >
                            {{ t('companyInterest.educationCta') }}
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

        <section id="new-education" class="brand-section relative scroll-mt-24 border-y border-border px-6 py-16 lg:px-16">
            <div class="mx-auto max-w-7xl">
                <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:gap-14">
                    <div>
                        <p class="brand-eyebrow">{{ t('companyInterest.educationEyebrow') }}</p>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">{{ t('companyInterest.educationTitle') }}</h2>
                        <p class="mt-5 text-base leading-relaxed text-muted-foreground">{{ t('companyInterest.educationIntro') }}</p>

                        <a
                            href="https://www.avans.nl/over-avans/organisatie/ambitie"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-primary transition hover:text-primary/80"
                        >
                            {{ t('companyInterest.readAvans') }}
                            <ExternalLink class="h-4 w-4" />
                        </a>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <article v-for="(icon, index) in [BookOpenCheck, Blocks, Route, GraduationCap]" :key="index" class="brand-card rounded-2xl p-5 sm:p-6">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-secondary/15 text-secondary ring-1 ring-secondary/20">
                                <component :is="icon" class="h-5 w-5" />
                            </div>
                            <h3 class="mt-4 text-lg font-semibold">{{ t(`companyInterest.educationPoint${index + 1}Title`) }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ t(`companyInterest.educationPoint${index + 1}Text`) }}</p>
                        </article>
                    </div>
                </div>

                <div class="brand-card mt-8 grid gap-7 rounded-3xl p-6 sm:p-8 lg:grid-cols-[0.7fr_1.3fr]">
                    <div>
                        <p class="brand-eyebrow">{{ t('companyInterest.forEmployersEyebrow') }}</p>
                        <h3 class="mt-4 text-2xl font-semibold tracking-tight">{{ t('companyInterest.forEmployersTitle') }}</h3>
                    </div>
                    <ul class="grid gap-4 sm:grid-cols-3">
                        <li v-for="index in 3" :key="index" class="flex gap-3 text-sm leading-relaxed text-muted-foreground">
                            <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                            {{ t(`companyInterest.employerTip${index}`) }}
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="internships" class="relative scroll-mt-24 px-6 py-16 sm:py-20 lg:px-16">
            <div class="mx-auto max-w-7xl">
                <div class="max-w-3xl">
                    <p class="brand-eyebrow">{{ t('companyInterest.internshipEyebrow') }}</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">{{ t('companyInterest.internshipTitle') }}</h2>
                    <p class="mt-4 text-base leading-relaxed text-muted-foreground">{{ t('companyInterest.internshipIntro') }}</p>
                </div>

                <div class="mt-9">
                    <div class="overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" role="tablist" :aria-label="t('companyInterest.chooseProgram')">
                        <span class="sr-only">{{ t('companyInterest.chooseProgram') }}</span>
                        <div class="flex min-w-max items-end gap-1.5 lg:min-w-0">
                            <button
                                v-for="program in internshipPrograms"
                                :id="`internship-tab-${program.id}`"
                                :key="program.id"
                                type="button"
                                role="tab"
                                :aria-selected="selectedProgramId === program.id"
                                :aria-controls="`internship-panel-${program.id}`"
                                class="group relative flex min-h-16 min-w-44 items-center justify-center gap-2.5 rounded-t-2xl border px-4 py-3 text-center text-sm font-semibold transition focus-visible:z-20 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none lg:min-w-0 lg:flex-1"
                                :class="
                                    selectedProgramId === program.id
                                        ? 'brand-card z-10 translate-y-px border-b-transparent text-primary shadow-none'
                                        : 'border-transparent bg-muted/50 text-muted-foreground hover:bg-muted hover:text-foreground'
                                "
                                @click="selectedProgramId = program.id"
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ring-1 transition"
                                    :class="selectedProgramId === program.id ? 'bg-primary/10 text-primary ring-primary/20' : 'bg-background/60 text-secondary ring-border'"
                                >
                                    <component :is="program.icon" class="h-4 w-4" />
                                </span>
                                <span class="max-w-36 leading-tight">{{ t(`companyInterest.internshipProgram.${program.id}.name`) }}</span>
                            </button>
                        </div>
                    </div>

                    <article
                        :id="`internship-panel-${selectedProgram.id}`"
                        class="brand-card -mt-px overflow-hidden rounded-3xl"
                        :class="{
                            'rounded-tl-none': selectedProgramId === internshipPrograms[0].id,
                            'rounded-tr-none': selectedProgramId === internshipPrograms[internshipPrograms.length - 1].id,
                        }"
                        role="tabpanel"
                        :aria-labelledby="`internship-tab-${selectedProgram.id}`"
                    >
                        <div class="border-b border-border p-6 sm:p-8">
                            <div>
                                <h3 class="text-2xl font-semibold tracking-tight">
                                    {{ t(`companyInterest.internshipProgram.${selectedProgram.id}.name`) }}
                                </h3>
                                <p class="mt-3 max-w-3xl text-sm leading-relaxed text-muted-foreground">
                                    {{ t(`companyInterest.internshipProgram.${selectedProgram.id}.summary`) }}
                                </p>
                            </div>

                            <div class="mt-8" :aria-label="t('companyInterest.timelineLabel')">
                                <div class="hidden sm:block">
                                    <div class="grid grid-cols-4 gap-2">
                                        <div v-for="year in 4" :key="year" class="text-center text-xs font-semibold text-muted-foreground">
                                            {{ t('companyInterest.year') }} {{ year }}
                                        </div>
                                    </div>
                                    <div class="mt-2 grid grid-cols-8 gap-1.5">
                                        <div
                                            v-for="(phase, index) in selectedProgram.phases"
                                            :key="index"
                                            class="flex min-h-20 items-center justify-center rounded-lg px-1 text-center text-xs leading-tight font-semibold ring-1"
                                            :class="internshipPhaseClasses[phase]"
                                            :title="t(`companyInterest.phase.${phase}`)"
                                        >
                                            {{ t(`companyInterest.phase.${phase}`) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2.5 sm:hidden">
                                    <div v-for="year in 4" :key="year" class="grid grid-cols-[3.5rem_1fr_1fr] items-stretch gap-2">
                                        <div class="flex items-center text-sm font-semibold text-muted-foreground">{{ t('companyInterest.year') }} {{ year }}</div>
                                        <div
                                            v-for="halfYear in 2"
                                            :key="halfYear"
                                            class="flex min-h-16 flex-col items-center justify-center rounded-xl px-2 text-center leading-tight ring-1"
                                            :class="internshipPhaseClasses[internshipPhaseAt(year, halfYear)]"
                                        >
                                            <span class="text-[10px] font-medium opacity-75">{{ t(`companyInterest.halfYear${halfYear}`) }}</span>
                                            <span class="mt-1 text-xs font-semibold">{{ t(`companyInterest.phase.${internshipPhaseAt(year, halfYear)}`) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 hidden flex-wrap gap-x-5 gap-y-2 text-xs text-muted-foreground sm:flex">
                                    <span v-for="phase in ['internship', 'orientation', 'graduation'] as InternshipPhase[]" :key="phase" class="inline-flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full ring-1" :class="internshipPhaseClasses[phase]"></span>
                                        {{ t(`companyInterest.phase.${phase}`) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-6 p-6 sm:p-8 md:grid-cols-2">
                            <div>
                                <h4 class="font-semibold">{{ t('companyInterest.assignmentTitle') }}</h4>
                                <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                                    {{ t(`companyInterest.internshipProgram.${selectedProgram.id}.assignment`) }}
                                </p>
                            </div>
                            <div>
                                <h4 class="font-semibold">{{ t('companyInterest.guidanceTitle') }}</h4>
                                <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ t('companyInterest.guidanceText') }}</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-4 border-t border-border bg-muted/30 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                            <p class="flex max-w-2xl gap-2 text-xs leading-relaxed text-muted-foreground">
                                <Info class="mt-0.5 h-4 w-4 shrink-0 text-secondary" />
                                {{ t('companyInterest.internshipDisclaimer') }}
                            </p>
                            <a
                                :href="selectedProgram.sourceUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-primary transition hover:text-primary/80"
                            >
                                {{ t('companyInterest.viewProgram') }}
                                <ExternalLink class="h-4 w-4" />
                            </a>
                        </div>
                    </article>
                </div>
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

                    <div class="mt-8 flex justify-end">
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
