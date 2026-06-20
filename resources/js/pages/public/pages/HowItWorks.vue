<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import Newsletter from '@/components/public/Newsletter.vue';
import Reveal from '@/components/public/Reveal.vue';
import { useI18n } from '@/composables/useI18n';

interface Step {
    t: string;
    d: string;
}

interface HowContent {
    title: string;
    subtitle: string;
    steps: Step[];
}

defineProps<{ content: HowContent }>();

const { t, locale } = useI18n();
</script>

<template>
    <Head :title="content.title" />

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <Breadcrumbs :items="[{ label: content.title }]" class="mb-5" />

        <Reveal class="text-center">
            <h1
                class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-gray-100"
            >
                {{ content.title }}
            </h1>
            <p class="mt-3 text-lg text-gray-500 dark:text-gray-400">
                {{ content.subtitle }}
            </p>
        </Reveal>

        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
            <Reveal
                v-for="(step, i) in content.steps"
                :key="i"
                :delay="i * 120"
            >
                <div
                    class="relative h-full rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <span
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-lg font-bold text-white"
                    >
                        {{ i + 1 }}
                    </span>
                    <h2
                        class="mt-4 font-semibold text-gray-900 dark:text-gray-100"
                    >
                        {{ step.t }}
                    </h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ step.d }}
                    </p>
                </div>
            </Reveal>
        </div>

        <Reveal class="mt-10 text-center">
            <Link
                :href="`/${locale}/stores`"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"
            >
                {{ t('hero.cta_explore') }} <ArrowRight class="h-4 w-4" />
            </Link>
        </Reveal>

        <div class="mt-14">
            <Newsletter />
        </div>
    </div>
</template>
