<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue'
import AppHeader from '@/components/AppHeader.vue'

interface Policy {
    policyHtml: string | null
    updatedAt?: string | null
}

const props = defineProps<{
    policy: Policy
}>()

const policy = props.policy
</script>

<template>
    <AppHeader class="sticky top-0 z-50" />

    <main class="relative overflow-hidden bg-background px-6 py-14 lg:px-16">
        <div class="relative mx-auto max-w-5xl">
            <div class="p-6">
                <div class="flex flex-col gap-1">
                    <p v-if="policy.updatedAt" class="text-sm text-muted-foreground">Last modified: {{ policy.updatedAt }}</p>
                    <h1 class="text-3xl font-semibold">Terms of service</h1>
                </div>

                <div
                    v-if="policy?.policyHtml"
                    class="prose prose-m mt-6 max-w-none dark:prose-invert"
                    v-html="policy.policyHtml"
                ></div>
                <p v-else class="mt-5 text-sm text-muted-foreground">There are no terms of service set yet.</p>
            </div>
        </div>
    </main>

    <AppFooter />
</template>
