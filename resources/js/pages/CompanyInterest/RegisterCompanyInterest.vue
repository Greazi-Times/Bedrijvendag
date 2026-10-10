<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    PhArrowDown,
    PhArrowUpRight,
    PhBookOpenText,
    PhBriefcase,
    PhCalendarBlank,
    PhChatsCircle,
    PhCheck,
    PhCheckCircle,
    PhCpu,
    PhCube,
    PhFileText,
    PhGraduationCap,
    PhInfo,
    PhLightning,
    PhPaperPlaneTilt,
    PhPath,
    PhWrench,
} from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import type { Component } from 'vue';

import PageIntro from '@/components/site/PageIntro.vue';
import SiteLayout from '@/components/site/SiteLayout.vue';
import { useTranslations } from '@/i18n';
import { focusFirstError } from '@/lib/forms';

type UpcomingEvent = {
    name: string;
    date: string | null;
};

type InternshipPhase = 'education' | 'orientation' | 'internship' | 'graduation';

type InternshipProgram = {
    id: string;
    icon: Component;
    sourceUrl: string;
    phases: InternshipPhase[];
};

const internshipPrograms: InternshipProgram[] = [
    {
        id: 'mechatronics',
        icon: PhCube,
        sourceUrl: 'https://www.avans.nl/studeren/opleidingen/bachelor/mechatronica-voltijd',
        phases: ['education', 'education', 'education', 'education', 'internship', 'education', 'education', 'graduation'],
    },
    {
        id: 'mechanical',
        icon: PhWrench,
        sourceUrl: 'https://www.avans.nl/studeren/opleidingen/bachelor/werktuigbouwkunde-voltijd',
        phases: ['education', 'education', 'education', 'education', 'internship', 'education', 'education', 'graduation'],
    },
    {
        id: 'electrical',
        icon: PhLightning,
        sourceUrl: 'https://www.avans.nl/studeren/opleidingen/bachelor/elektrotechniek-voltijd',
        phases: ['education', 'education', 'education', 'education', 'internship', 'education', 'education', 'graduation'],
    },
    {
        id: 'ict',
        icon: PhCpu,
        sourceUrl: 'https://www.avans.nl/studeren/opleidingen/bachelor/ict-voltijd',
        phases: ['education', 'education', 'education', 'education', 'internship', 'education', 'education', 'graduation'],
    },
    {
        id: 'businessIt',
        icon: PhBriefcase,
        sourceUrl: 'https://www.avans.nl/studeren/opleidingen/bachelor/business-it-en-management-voltijd',
        phases: ['education', 'education', 'education', 'education', 'internship', 'education', 'education', 'graduation'],
    },
    {
        id: 'industrial',
        icon: PhPath,
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

// Roving tabindex: arrow keys move between programme tabs (WAI-ARIA tabs pattern).
function onTabKeydown(event: KeyboardEvent) {
    const keys = ['ArrowRight', 'ArrowLeft', 'Home', 'End'];
    if (!keys.includes(event.key)) return;
    event.preventDefault();

    const index = internshipPrograms.findIndex((program) => program.id === selectedProgramId.value);
    const last = internshipPrograms.length - 1;
    const next = { ArrowRight: index === last ? 0 : index + 1, ArrowLeft: index === 0 ? last : index - 1, Home: 0, End: last }[event.key] ?? index;

    selectedProgramId.value = internshipPrograms[next].id;
    document.getElementById(`internship-tab-${internshipPrograms[next].id}`)?.focus();
}

function internshipPhaseAt(year: number, halfYear: number): InternshipPhase {
    return selectedProgram.value.phases[(year - 1) * 2 + (halfYear - 1)] ?? 'education';
}

const internshipPhaseClasses: Record<InternshipPhase, string> = {
    education: 'bg-surface text-ink-muted ring-hairline',
    orientation: 'bg-brand-blue/12 text-brand-blue ring-brand-blue/30',
    internship: 'tile-sunset ring-transparent',
    graduation: 'bg-ink text-canvas ring-transparent',
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
        onError: focusFirstError,
        onSuccess: () => {
            saved.value = true;
            form.reset();
        },
    });
}

const educationPoints: Component[] = [PhBookOpenText, PhCube, PhPath, PhGraduationCap];
const processSteps: Component[] = [PhPaperPlaneTilt, PhChatsCircle, PhFileText];
</script>

<template>
    <Head :title="t('companyInterest.head')" />

    <SiteLayout>
        <PageIntro :eyebrow="t('companyInterest.eyebrow')" :title="t('companyInterest.title')" :lead="t('companyInterest.intro')">
            <div class="flex flex-wrap gap-3">
                <a href="#enroll" class="btn btn-primary btn-lg">
                    {{ t('companyInterest.startEnrollment') }}
                    <PhArrowDown :size="18" weight="bold" aria-hidden="true" />
                </a>
                <a href="#new-education" class="btn btn-secondary btn-lg">{{ t('companyInterest.educationCta') }}</a>
            </div>

            <template #aside>
                <aside class="shadow-raised rounded-[var(--radius-card)] bg-canvas p-6 ring-1 ring-hairline" aria-labelledby="next-event-title">
                    <div class="flex items-start gap-4">
                        <span class="tile-sunset flex size-11 shrink-0 items-center justify-center rounded-[var(--radius-input)]"
                            ><PhCalendarBlank :size="20" weight="bold" aria-hidden="true"
                        /></span>
                        <div>
                            <p id="next-event-title" class="text-sm text-ink-muted">{{ t('companyInterest.nextEvent') }}</p>
                            <template v-if="props.upcomingEvent">
                                <h2 class="mt-1 text-lg font-semibold text-ink">{{ props.upcomingEvent.name }}</h2>
                                <p class="mt-1 text-sm text-brand-ink first-letter:uppercase">{{ formattedEventDate }}</p>
                            </template>
                            <template v-else>
                                <h2 class="mt-1 text-lg font-semibold text-ink">{{ t('companyInterest.dateComingSoon') }}</h2>
                                <p class="mt-1 text-sm text-ink-muted">{{ t('companyInterest.stillInterested') }}</p>
                            </template>
                        </div>
                    </div>
                    <ul class="mt-6 space-y-3 border-t border-hairline pt-6">
                        <li v-for="benefit in ['audience', 'format', 'opportunities']" :key="benefit" class="flex gap-3 text-sm leading-relaxed text-ink-muted">
                            <PhCheck :size="16" weight="bold" class="mt-0.5 shrink-0 text-brand-ink" aria-hidden="true" />
                            {{ t(`companyInterest.${benefit}`) }}
                        </li>
                    </ul>
                </aside>
            </template>
        </PageIntro>

        <!-- New education -->
        <section id="new-education" class="scroll-mt-24 py-20 md:py-28">
            <div class="site-container">
                <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-14">
                    <div class="lg:col-span-5">
                        <p v-reveal class="t-eyebrow">{{ t('companyInterest.educationEyebrow') }}</p>
                        <h2 v-reveal="40" class="t-h2 mt-5 text-ink">{{ t('companyInterest.educationTitle') }}</h2>
                        <p v-reveal="80" class="t-lead mt-6">{{ t('companyInterest.educationIntro') }}</p>
                        <a v-reveal="120" href="https://www.avans.nl/over-avans/organisatie/ambitie" target="_blank" rel="noopener noreferrer" class="btn btn-secondary mt-8">
                            {{ t('companyInterest.readAvans') }}
                            <PhArrowUpRight :size="16" weight="bold" aria-hidden="true" />
                        </a>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:col-span-7">
                        <article
                            v-for="(icon, index) in educationPoints"
                            :key="index"
                            v-reveal="(index % 2) * 80"
                            class="card-muted flex flex-col rounded-[var(--radius-card)] p-7"
                        >
                            <component :is="icon" :size="28" class="text-brand-ink" aria-hidden="true" />
                            <h3 class="t-h4 mt-8 text-ink">{{ t(`companyInterest.educationPoint${index + 1}Title`) }}</h3>
                            <p class="mt-2 text-[0.9375rem] leading-relaxed text-ink-muted">
                                {{ t(`companyInterest.educationPoint${index + 1}Text`) }}
                            </p>
                        </article>
                    </div>
                </div>

                <div v-reveal class="tile-blue-soft mt-16 grid grid-cols-1 gap-8 rounded-[var(--radius-panel)] p-8 sm:p-10 lg:grid-cols-12 lg:p-12">
                    <div class="lg:col-span-4">
                        <p class="t-eyebrow">{{ t('companyInterest.forEmployersEyebrow') }}</p>
                        <h3 class="t-h3 mt-4 text-ink">{{ t('companyInterest.forEmployersTitle') }}</h3>
                    </div>
                    <ul class="grid gap-6 sm:grid-cols-3 lg:col-span-8">
                        <li v-for="index in 3" :key="index" class="border-t border-hairline pt-5 text-[0.9375rem] leading-relaxed text-ink-muted">
                            <PhCheckCircle :size="22" weight="fill" class="mb-4 text-brand-ink" aria-hidden="true" />
                            {{ t(`companyInterest.employerTip${index}`) }}
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Internships per programme -->
        <section id="internships" class="scroll-mt-24 py-20 md:py-28">
            <div class="site-container">
                <div class="max-w-3xl">
                    <p v-reveal class="t-eyebrow">{{ t('companyInterest.internshipEyebrow') }}</p>
                    <h2 v-reveal="40" class="t-h2 mt-5 text-ink">{{ t('companyInterest.internshipTitle') }}</h2>
                    <p v-reveal="80" class="t-lead mt-5">{{ t('companyInterest.internshipIntro') }}</p>
                </div>

                <div class="mt-12">
                    <div
                        class="-mx-5 overflow-x-auto px-5 [scrollbar-width:none] md:mx-0 md:px-0 [&::-webkit-scrollbar]:hidden"
                        role="tablist"
                        :aria-label="t('companyInterest.chooseProgram')"
                    >
                        <div class="inline-flex min-w-max gap-1 rounded-[var(--radius-card)] bg-canvas p-1.5 ring-1 ring-hairline">
                            <button
                                v-for="program in internshipPrograms"
                                :id="`internship-tab-${program.id}`"
                                :key="program.id"
                                type="button"
                                role="tab"
                                :aria-selected="selectedProgramId === program.id"
                                :aria-controls="`internship-panel-${program.id}`"
                                :tabindex="selectedProgramId === program.id ? 0 : -1"
                                class="inline-flex min-h-11 items-center gap-2 rounded-[var(--radius-input)] px-4 text-sm font-medium whitespace-nowrap transition-colors"
                                :class="selectedProgramId === program.id ? 'bg-ink text-canvas' : 'text-ink-muted hover:bg-surface hover:text-ink'"
                                @click="selectedProgramId = program.id"
                                @keydown="onTabKeydown"
                            >
                                <component :is="program.icon" :size="16" weight="bold" aria-hidden="true" />
                                {{ t(`companyInterest.internshipProgram.${program.id}.name`) }}
                            </button>
                        </div>
                    </div>

                    <article
                        :id="`internship-panel-${selectedProgram.id}`"
                        :key="selectedProgram.id"
                        class="card mt-4 overflow-hidden"
                        role="tabpanel"
                        :aria-labelledby="`internship-tab-${selectedProgram.id}`"
                    >
                        <div class="border-b border-hairline p-6 sm:p-10">
                            <h3 class="t-h2 text-ink">{{ t(`companyInterest.internshipProgram.${selectedProgram.id}.name`) }}</h3>
                            <p class="t-lead mt-4 max-w-3xl">{{ t(`companyInterest.internshipProgram.${selectedProgram.id}.summary`) }}</p>

                            <div class="mt-10" :aria-label="t('companyInterest.timelineLabel')">
                                <div class="hidden sm:block">
                                    <div class="grid grid-cols-4 gap-2">
                                        <div v-for="year in 4" :key="year" class="t-mono text-center text-xs font-medium text-ink-muted">
                                            {{ t('companyInterest.year') }} {{ year }}
                                        </div>
                                    </div>
                                    <div class="mt-2 grid grid-cols-8 gap-1.5">
                                        <div
                                            v-for="(phase, index) in selectedProgram.phases"
                                            :key="index"
                                            class="flex min-h-24 items-center justify-center rounded-[var(--radius-input)] px-1 text-center text-xs leading-tight font-semibold ring-1"
                                            :class="internshipPhaseClasses[phase]"
                                            :title="t(`companyInterest.phase.${phase}`)"
                                        >
                                            {{ t(`companyInterest.phase.${phase}`) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2 sm:hidden">
                                    <div v-for="year in 4" :key="year" class="grid grid-cols-[3.5rem_1fr_1fr] items-stretch gap-2">
                                        <div class="t-mono flex items-center text-xs font-medium text-ink-muted">{{ t('companyInterest.year') }} {{ year }}</div>
                                        <div
                                            v-for="halfYear in 2"
                                            :key="halfYear"
                                            class="flex min-h-16 flex-col items-center justify-center rounded-[var(--radius-input)] px-2 text-center leading-tight ring-1"
                                            :class="internshipPhaseClasses[internshipPhaseAt(year, halfYear)]"
                                        >
                                            <span class="text-[10px] font-medium opacity-75">{{ t(`companyInterest.halfYear${halfYear}`) }}</span>
                                            <span class="mt-1 text-xs font-semibold">{{ t(`companyInterest.phase.${internshipPhaseAt(year, halfYear)}`) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 hidden flex-wrap gap-x-6 gap-y-2 text-xs text-ink-muted sm:flex">
                                    <span v-for="phase in ['internship', 'orientation', 'graduation'] as InternshipPhase[]" :key="phase" class="inline-flex items-center gap-2">
                                        <span class="size-3 rounded-full ring-1" :class="internshipPhaseClasses[phase]"></span>
                                        {{ t(`companyInterest.phase.${phase}`) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-8 p-6 sm:p-10 md:grid-cols-2">
                            <div>
                                <h4 class="t-h4 text-ink">{{ t('companyInterest.assignmentTitle') }}</h4>
                                <p class="t-body mt-2">{{ t(`companyInterest.internshipProgram.${selectedProgram.id}.assignment`) }}</p>
                            </div>
                            <div>
                                <h4 class="t-h4 text-ink">{{ t('companyInterest.guidanceTitle') }}</h4>
                                <p class="t-body mt-2">{{ t('companyInterest.guidanceText') }}</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-4 border-t border-hairline bg-surface/70 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-10">
                            <p class="flex max-w-2xl gap-2 text-sm leading-relaxed text-ink-muted">
                                <PhInfo :size="18" class="mt-0.5 shrink-0 text-brand-blue" aria-hidden="true" />
                                {{ t('companyInterest.internshipDisclaimer') }}
                            </p>
                            <a :href="selectedProgram.sourceUrl" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm shrink-0">
                                {{ t('companyInterest.viewProgram') }}
                                <PhArrowUpRight :size="14" weight="bold" aria-hidden="true" />
                            </a>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Process -->
        <section id="how-it-works" class="border-y border-hairline bg-surface py-20 md:py-28">
            <div class="site-container">
                <div class="max-w-2xl">
                    <p v-reveal class="t-eyebrow">{{ t('companyInterest.processEyebrow') }}</p>
                    <h2 v-reveal="40" class="t-h2 mt-5 text-ink">{{ t('companyInterest.processTitle') }}</h2>
                    <p v-reveal="80" class="t-lead mt-5">{{ t('companyInterest.processIntro') }}</p>
                </div>

                <ol class="mt-14 grid gap-3 md:grid-cols-3">
                    <li v-for="(icon, index) in processSteps" :key="index" v-reveal="index * 80" class="spot relative bg-canvas p-7 md:p-8">
                        <div class="flex items-center justify-between">
                            <span class="tile-sunset flex size-11 items-center justify-center rounded-[var(--radius-input)]">
                                <component :is="icon" :size="22" weight="bold" aria-hidden="true" />
                            </span>
                            <span class="t-mono text-sm text-ink-subtle" aria-hidden="true">0{{ index + 1 }}</span>
                        </div>
                        <h3 class="t-h3 mt-8 text-ink">{{ t(`companyInterest.step${index + 1}Title`) }}</h3>
                        <p class="t-body mt-2">{{ t(`companyInterest.step${index + 1}Text`) }}</p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Form -->
        <section id="enroll" class="scroll-mt-24 py-20 md:py-28">
            <div class="site-container grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-16">
                <div class="lg:col-span-4">
                    <p v-reveal class="t-eyebrow">{{ t('companyInterest.formEyebrow') }}</p>
                    <h2 v-reveal="40" class="t-h2 mt-4 text-ink">
                        {{ t('companyInterest.formTitle') }}
                    </h2>
                    <p v-reveal="80" class="t-lead mt-5">{{ t('companyInterest.formIntro') }}</p>
                </div>

                <div class="lg:col-span-8">
                    <div aria-live="polite">
                        <p v-if="saved" class="alert alert-success mb-6" role="status">
                            <PhCheckCircle :size="20" weight="fill" class="shrink-0 text-success" aria-hidden="true" />
                            {{ t('companyInterest.success') }}
                        </p>
                    </div>

                    <form v-reveal="80" class="card grid gap-6 p-6 sm:grid-cols-2 sm:p-10" @submit.prevent="submit">
                        <div class="field">
                            <label for="company_name" class="field-label">{{ t('companyInterest.companyName') }}<span class="text-danger" aria-hidden="true"> *</span></label>
                            <input
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                autocomplete="organization"
                                :placeholder="t('companyInterest.companyPlaceholder')"
                                class="input"
                                :aria-invalid="form.errors.company_name ? 'true' : undefined"
                                :aria-describedby="form.errors.company_name ? 'company_name-error' : undefined"
                            />
                            <p v-if="form.errors.company_name" id="company_name-error" class="field-error">{{ form.errors.company_name }}</p>
                        </div>

                        <div class="field">
                            <label for="website_url" class="field-label">{{ t('common.website') }}</label>
                            <input
                                id="website_url"
                                v-model="form.website_url"
                                type="url"
                                placeholder="https://example.com"
                                class="input"
                                :aria-invalid="form.errors.website_url ? 'true' : undefined"
                                :aria-describedby="form.errors.website_url ? 'website_url-error' : undefined"
                            />
                            <p v-if="form.errors.website_url" id="website_url-error" class="field-error">{{ form.errors.website_url }}</p>
                        </div>

                        <div class="field">
                            <label for="contact_name" class="field-label">{{ t('companyInterest.contactPerson') }}<span class="text-danger" aria-hidden="true"> *</span></label>
                            <input
                                id="contact_name"
                                v-model="form.contact_name"
                                type="text"
                                autocomplete="name"
                                class="input"
                                :aria-invalid="form.errors.contact_name ? 'true' : undefined"
                                :aria-describedby="form.errors.contact_name ? 'contact_name-error' : undefined"
                            />
                            <p v-if="form.errors.contact_name" id="contact_name-error" class="field-error">{{ form.errors.contact_name }}</p>
                        </div>

                        <div class="field">
                            <label for="contact_email" class="field-label">{{ t('companyInterest.businessEmail') }}<span class="text-danger" aria-hidden="true"> *</span></label>
                            <input
                                id="contact_email"
                                v-model="form.contact_email"
                                type="email"
                                autocomplete="email"
                                spellcheck="false"
                                class="input"
                                :aria-invalid="form.errors.contact_email ? 'true' : undefined"
                                :aria-describedby="form.errors.contact_email ? 'contact_email-error' : undefined"
                            />
                            <p v-if="form.errors.contact_email" id="contact_email-error" class="field-error">{{ form.errors.contact_email }}</p>
                        </div>

                        <div class="field sm:col-span-2">
                            <label for="message" class="field-label">{{ t('companyInterest.editionQuestion') }}</label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                rows="4"
                                :placeholder="t('companyInterest.messagePlaceholder')"
                                class="input"
                                :aria-invalid="form.errors.message ? 'true' : undefined"
                                :aria-describedby="form.errors.message ? 'message-error' : undefined"
                            ></textarea>
                            <p v-if="form.errors.message" id="message-error" class="field-error">{{ form.errors.message }}</p>
                        </div>

                        <div class="flex justify-end sm:col-span-2">
                            <button type="submit" :disabled="form.processing" class="btn btn-primary btn-lg">
                                <PhPaperPlaneTilt :size="18" weight="bold" aria-hidden="true" />
                                {{ form.processing ? t('common.submitting') : t('companyInterest.sendRequest') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>
