<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import Reveal from '@/components/public/Reveal.vue';

interface Section {
    h: string;
    p: string;
}

interface PageContent {
    title: string;
    intro?: string;
    updated?: string;
    body?: string[];
    sections?: Section[];
}

defineProps<{ content: PageContent }>();
</script>

<template>
    <Head :title="content.title" />

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <Breadcrumbs :items="[{ label: content.title }]" class="mb-5" />

        <Reveal>
            <h1
                class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl"
            >
                {{ content.title }}
            </h1>
            <p v-if="content.updated" class="mt-2 text-sm text-gray-400">
                {{ content.updated }}: 2026
            </p>
            <p v-if="content.intro" class="mt-4 text-lg text-gray-500">
                {{ content.intro }}
            </p>
        </Reveal>

        <div class="mt-6 space-y-4 text-gray-600">
            <Reveal
                v-for="(paragraph, i) in content.body ?? []"
                :key="`b-${i}`"
            >
                <p class="leading-relaxed">{{ paragraph }}</p>
            </Reveal>

            <Reveal
                v-for="(section, i) in content.sections ?? []"
                :key="`s-${i}`"
                class="pt-2"
            >
                <h2 class="text-lg font-bold text-gray-900">{{ section.h }}</h2>
                <p class="mt-1 leading-relaxed">{{ section.p }}</p>
            </Reveal>
        </div>
    </div>
</template>
