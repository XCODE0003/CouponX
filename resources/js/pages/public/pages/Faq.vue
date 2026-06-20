<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { ref } from 'vue';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import Reveal from '@/components/public/Reveal.vue';

interface QA {
    q: string;
    a: string;
}

interface FaqContent {
    title: string;
    items: QA[];
}

defineProps<{ content: FaqContent }>();

const open = ref<number | null>(0);

function toggle(i: number): void {
    open.value = open.value === i ? null : i;
}
</script>

<template>
    <Head :title="content.title" />

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <Breadcrumbs :items="[{ label: content.title }]" class="mb-5" />

        <Reveal>
            <h1
                class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-gray-100"
            >
                {{ content.title }}
            </h1>
        </Reveal>

        <div class="mt-8 space-y-3">
            <Reveal v-for="(item, i) in content.items" :key="i" :delay="i * 80">
                <div
                    class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                        :aria-expanded="open === i"
                        @click="toggle(i)"
                    >
                        <span
                            class="font-semibold text-gray-900 dark:text-gray-100"
                            >{{ item.q }}</span
                        >
                        <ChevronDown
                            class="h-5 w-5 shrink-0 text-gray-400 transition-transform duration-300 dark:text-gray-500"
                            :class="{ 'rotate-180': open === i }"
                        />
                    </button>
                    <div
                        class="grid transition-all duration-300 ease-in-out"
                        :class="
                            open === i
                                ? 'grid-rows-[1fr] opacity-100'
                                : 'grid-rows-[0fr] opacity-0'
                        "
                    >
                        <div class="overflow-hidden">
                            <p
                                class="px-5 pb-4 text-sm leading-relaxed text-gray-500 dark:text-gray-400"
                            >
                                {{ item.a }}
                            </p>
                        </div>
                    </div>
                </div>
            </Reveal>
        </div>
    </div>
</template>
