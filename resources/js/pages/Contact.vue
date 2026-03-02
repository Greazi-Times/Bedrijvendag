<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, MapPin, Phone, Send, CheckCircle2, Linkedin, Facebook, Twitter } from 'lucide-vue-next';
import { onBeforeUnmount, ref } from 'vue';

import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
});

const showSuccess = ref(false);
let successTimeout: number | undefined;

const triggerSuccess = () => {
    showSuccess.value = true;

    if (successTimeout) window.clearTimeout(successTimeout);

    successTimeout = window.setTimeout(() => {
        showSuccess.value = false;
    }, 7000);
};

onBeforeUnmount(() => {
    if (successTimeout) window.clearTimeout(successTimeout);
});
</script>

<template>
    <Head title="Contact" />

    <AppHeader class="sticky top-0 z-50" />

    <section id="support" class="relative overflow-hidden bg-background px-6 py-20 lg:px-16 lg:py-24">
        <!-- Decorative circles like Home -->
        <div class="pointer-events-none absolute -top-10 -right-6 h-72 w-72 rounded-full bg-secondary/20"></div>
        <div class="pointer-events-none absolute top-24 -left-8 h-28 w-28 rounded-[2.25rem] bg-primary/25"></div>
        <div class="pointer-events-none absolute right-10 -bottom-8 h-20 w-20 rounded-full bg-accent"></div>
        <div class="pointer-events-none absolute bottom-10 left-10 h-36 w-36 rounded-full bg-primary/10"></div>

        <div class="relative mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold text-primary">Contact</p>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">Neem contact op</h1>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">
                    Stuur ons een bericht. We reageren zo snel mogelijk.
                </p>
            </div>

            <div class="mt-12 flex flex-col-reverse gap-7.5 md:flex-row md:items-start md:justify-between lg:mt-20 xl:gap-10">
                <!-- Left: contact info -->
                <aside class="relative w-full overflow-hidden rounded-2xl bg-background p-7.5 shadow-sm ring-1 ring-border md:w-2/5 lg:w-1/3 xl:p-10">
                    <div class="pointer-events-none absolute -top-10 -right-10 h-24 w-24 rounded-full bg-primary/10"></div>
                    <div class="pointer-events-none absolute -bottom-10 -left-10 h-24 w-24 rounded-full bg-secondary/10"></div>

                    <div class="space-y-7">
                        <div class="flex items-start gap-4">
                            <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/15">
                                <MapPin class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-foreground">Adres</p>
                                <p class="mt-1 text-sm leading-relaxed text-muted-foreground">Lovensdijkstraat 61, 4818 AJ, Breda, Nederland</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/15">
                                <Phone class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-foreground">Telefoon</p>
                                <a class="mt-1 inline-block text-sm text-muted-foreground hover:text-foreground" href="tel:+31885258600">088-5258600</a>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/15">
                                <Mail class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-foreground">E-mail</p>
                                <a class="mt-1 inline-block break-all text-sm text-muted-foreground hover:text-foreground" href="mailto:bedrijvendag.atix@avans.nl">bedrijvendag.atix@avans.nl</a>
                            </div>
                        </div>
                    </div>

                    <span class="my-8 block h-px bg-border"></span>

                    <p class="text-sm text-muted-foreground">
                        Liever direct contact? Mail of bel ons.
                    </p>
                </aside>

                <!-- Right: form -->
                <div class="relative w-full overflow-hidden rounded-2xl bg-background p-7.5 shadow-sm ring-1 ring-border md:w-3/5 lg:w-2/3 xl:p-12">
                    <div class="pointer-events-none absolute -top-10 -right-10 h-32 w-32 rounded-full bg-primary/10"></div>
                    <div class="pointer-events-none absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-secondary/10"></div>
                    <form
                        class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-10 lg:gap-y-6"
                        @submit.prevent="
                            form.post('/contact', {
                                preserveScroll: true,
                                onSuccess: () => {
                                    triggerSuccess();
                                    form.reset('name', 'email', 'phone', 'subject', 'message');
                                },
                            })
                        "
                    >
                        <div>
                            <label for="name" class="mb-2 block text-sm font-semibold text-foreground">Naam <span class="text-destructive">*</span></label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                name="name"
                                autocomplete="name"
                                placeholder="Voor- en achternaam"
                                class="w-full rounded-xl bg-background px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                                required
                            />
                            <p v-if="form.errors.name" class="mt-2 text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-foreground">E-mail <span class="text-destructive">*</span></label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                name="email"
                                autocomplete="email"
                                placeholder="voorbeeld@mail.com"
                                class="w-full rounded-xl bg-background px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                                required
                            />
                            <p v-if="form.errors.email" class="mt-2 text-sm text-destructive">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label for="phone" class="mb-2 block text-sm font-semibold text-foreground">Telefoon (optioneel)</label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                name="phone"
                                autocomplete="tel"
                                placeholder="+31 6 12345678"
                                class="w-full rounded-xl bg-background px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                            />
                            <p v-if="form.errors.phone" class="mt-2 text-sm text-destructive">{{ form.errors.phone }}</p>
                        </div>

                        <div>
                            <label for="subject" class="mb-2 block text-sm font-semibold text-foreground">Onderwerp <span class="text-destructive">*</span></label>
                            <input
                                id="subject"
                                v-model="form.subject"
                                type="text"
                                name="subject"
                                placeholder="Waar gaat je vraag over?"
                                class="w-full rounded-xl bg-background px-4 py-3 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                                required
                            />
                            <p v-if="form.errors.subject" class="mt-2 text-sm text-destructive">{{ form.errors.subject }}</p>
                        </div>

                        <div class="lg:col-span-2">
                            <label for="message" class="mb-2 block text-sm font-semibold text-foreground">Bericht <span class="text-destructive">*</span></label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                name="message"
                                rows="6"
                                placeholder="Schrijf je bericht..."
                                class="w-full rounded-xl bg-background p-4 text-sm text-foreground ring-1 ring-border transition focus:ring-2 focus:ring-ring/40 focus:outline-none"
                                required
                            ></textarea>
                            <p v-if="form.errors.message" class="mt-2 text-sm text-destructive">{{ form.errors.message }}</p>
                        </div>

                        <div class="lg:col-span-2 flex flex-col items-center gap-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center justify-center gap-2 rounded-full bg-primary px-8 py-3 text-sm font-semibold text-primary-foreground shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <Send class="h-4 w-4" />
                                {{ form.processing ? 'Versturen...' : 'Verstuur bericht' }}
                            </button>

                            <p class="text-xs text-muted-foreground">Je gegevens gebruiken we alleen om te reageren.</p>

                            <div
                                v-if="showSuccess"
                                class="w-full max-w-xl rounded-xl bg-emerald-500/15 p-4 text-sm text-emerald-900 ring-1 ring-emerald-500/25"
                                role="status"
                                aria-live="polite"
                            >
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 class="h-5 w-5 shrink-0 text-emerald-600" />
                                    <span>Bedankt. We hebben je bericht ontvangen.</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <AppFooter />
</template>

<style scoped></style>
