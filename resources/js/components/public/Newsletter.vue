<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const form = useForm({ email: '' });

function submit(): void {
    form.post(
        '/' + (window.location.pathname.split('/')[1] || 'en') + '/newsletter',
        {
            preserveScroll: true,
            onSuccess: () => form.reset('email'),
        },
    );
}
</script>

<template>
    <section
        class="rounded-3xl bg-blue-50/70 px-6 py-8 sm:px-10 dark:bg-blue-950/20"
    >
        <div
            class="flex flex-col items-start gap-6 lg:flex-row lg:items-center lg:justify-between"
        >
            <div class="flex items-start gap-4">
                <span
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white"
                >
                    <Mail class="h-6 w-6" />
                </span>
                <div>
                    <h2
                        class="text-xl font-bold text-gray-900 dark:text-gray-100"
                    >
                        {{ t('newsletter.title') }}
                    </h2>
                    <p
                        class="mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400"
                    >
                        {{ t('newsletter.subtitle') }}
                    </p>
                </div>
            </div>
            <form class="w-full max-w-md" @submit.prevent="submit">
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        :placeholder="t('newsletter.placeholder')"
                        class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100"
                    />
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="shrink-0 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                    >
                        {{ t('newsletter.button') }}
                    </button>
                </div>
                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">
                    {{ form.errors.email }}
                </p>
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                    {{ t('newsletter.nospam') }}
                </p>
            </form>
        </div>
    </section>
</template>
