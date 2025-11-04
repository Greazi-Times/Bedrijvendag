<script lang="ts">
import { defineComponent } from 'vue';
import AppHeader from '@/components/AppHeader.vue';
import AppFooter from '@/components/AppFooter.vue';

export default defineComponent({
    name: 'CookiePolicy',
    components: { AppFooter, AppHeader },
});
</script>

<template>
    <AppHeader class="sticky top-0 z-50" />
    <section class="mx-auto max-w-4xl px-6 py-12 text-gray-800">
        <h1 class="mb-6 text-3xl font-bold">Cookie Policy</h1>
        <p class="mb-4">
            This Cookie Policy explains how ATIx Bedrijvendag (“we”, “our”, or “us”) uses cookies and similar technologies on our website at
            <strong>https://atixbedrijvendag.nl</strong>. This policy should be read together with our
            <a href="/privacy-policy" class="text-blue-600 underline">Privacy Policy</a>.
        </p>

        <h2 class="mt-8 mb-3 text-2xl font-semibold">1. What Are Cookies?</h2>
        <p class="mb-4">
            Cookies are small text files placed on your device when you visit a website. They help the website recognize your browser and store
            information about your preferences or past actions.
        </p>

        <h2 class="mt-8 mb-3 text-2xl font-semibold">2. How We Use Cookies</h2>
        <p class="mb-4">
            ATIx Bedrijvendag uses cookies strictly necessary for the operation and secure access of our website. These cookies are essential to
            provide the core features of the site, such as authentication and maintaining user sessions.
        </p>
        <p class="mb-4">We do not use third-party tracking or analytics cookies, nor do we use advertising or marketing cookies.</p>

        <h2 class="mt-8 mb-3 text-2xl font-semibold">3. Types of Cookies We Use</h2>
        <ul class="mb-4 ml-6 list-disc">
            <li><strong>Essential Cookies:</strong> Required for logging in, session management, and ensuring website functionality.</li>
            <li><strong>Functional Cookies:</strong> Used to remember user preferences and ensure a consistent experience across visits.</li>
        </ul>

        <h2 class="mt-8 mb-3 text-2xl font-semibold">4. Managing Cookies</h2>
        <p class="mb-4">
            Since our cookies are essential for website operation, disabling them may result in certain parts of the site not functioning properly.
            You can manage or delete cookies in your browser settings. For detailed instructions, please refer to your browser’s help documentation.
        </p>

        <h2 class="mt-8 mb-3 text-2xl font-semibold">5. Changes to This Cookie Policy</h2>
        <p class="mb-4">
            We may update this Cookie Policy to reflect changes in technology or legal requirements. The updated version will be posted on this page
            with a new “Last updated” date.
        </p>

        <h2 class="mt-8 mb-3 text-2xl font-semibold">6. Contact Us</h2>
        <p class="mb-4">If you have any questions about this Cookie Policy, please contact us at:</p>
        <p>
            ATIx Bedrijvendag<br />
            Lovensdijkstraat 61, Breda, 4818 AJ, The Netherlands<br />
            Email: <a href="mailto:privacy@atixbedrijvendag.nl" class="text-blue-600 underline">privacy@atixbedrijvendag.nl</a>
        </p>

        <p class="mt-8 text-gray-600 italic">Last updated: November 2025</p>
    </section>
    <AppFooter />
</template>

<style scoped></style>
