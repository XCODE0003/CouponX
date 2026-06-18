<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import Reveal from '@/components/public/Reveal.vue';
import { useI18n } from '@/composables/useI18n';

interface ContactContent {
    title: string;
    intro: string;
    name_label: string;
    email_label: string;
    message_label: string;
    send: string;
    reach_us: string;
}

defineProps<{ content: ContactContent; reachEmail?: string | null }>();

const { locale } = useI18n();

const form = useForm({ name: '', email: '', message: '' });

function submit(): void {
    form.post(`/${locale.value}/contact`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head :title="content.title" />

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <Breadcrumbs :items="[{ label: content.title }]" class="mb-5" />

        <Reveal>
            <h1
                class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl"
            >
                {{ content.title }}
            </h1>
            <p class="mt-3 text-gray-500">{{ content.intro }}</p>
        </Reveal>

        <Reveal class="mt-8">
            <form
                class="space-y-4 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                @submit.prevent="submit"
            >
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700"
                        >{{ content.name_label }}</label
                    >
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-lg border border-gray-200 px-4 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700"
                        >{{ content.email_label }}</label
                    >
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full rounded-lg border border-gray-200 px-4 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                    />
                    <p
                        v-if="form.errors.email"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>
                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-gray-700"
                        >{{ content.message_label }}</label
                    >
                    <textarea
                        v-model="form.message"
                        rows="5"
                        required
                        class="w-full rounded-lg border border-gray-200 px-4 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                    ></textarea>
                    <p
                        v-if="form.errors.message"
                        class="mt-1 text-xs text-rose-600"
                    >
                        {{ form.errors.message }}
                    </p>
                </div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                >
                    {{ content.send }}
                </button>
            </form>

            <p
                v-if="reachEmail"
                class="mt-4 flex items-center gap-2 text-sm text-gray-500"
            >
                <Mail class="h-4 w-4" /> {{ content.reach_us }}
                <a
                    :href="`mailto:${reachEmail}`"
                    class="font-medium text-blue-600 hover:underline"
                    >{{ reachEmail }}</a
                >
            </p>
        </Reveal>
    </div>
</template>
