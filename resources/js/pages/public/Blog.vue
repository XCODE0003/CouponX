<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import { useI18n } from '@/composables/useI18n';
import type { BlogPostData, Pagination } from '@/types/public';

defineProps<{ posts: BlogPostData[]; pagination: Pagination }>();

const { t } = useI18n();

function formatDate(iso: string | null): string {
    return iso ? new Date(iso).toLocaleDateString() : '';
}
</script>

<template>
    <Head :title="t('blog.title')" />

    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
        <Breadcrumbs :items="[{ label: t('nav.blog') }]" class="mb-4" />
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            {{ t('blog.title') }}
        </h1>
        <p class="mt-1 text-gray-500 dark:text-gray-400">
            {{ t('blog.subtitle') }}
        </p>

        <div
            v-if="posts.length"
            class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2"
        >
            <Link
                v-for="post in posts"
                :key="post.id"
                :href="post.url"
                class="group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="aspect-[16/9] w-full overflow-hidden bg-gray-100 dark:bg-gray-800"
                >
                    <img
                        v-if="post.cover_image"
                        :src="post.cover_image"
                        :alt="post.title"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-50 to-blue-100 text-blue-300 dark:from-blue-950/30 dark:to-blue-950/40"
                    >
                        <span class="text-3xl font-bold">{{
                            post.title.charAt(0)
                        }}</span>
                    </div>
                </div>
                <div class="flex flex-1 flex-col p-5">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        {{ formatDate(post.published_at) }}
                    </p>
                    <h2
                        class="mt-1 text-lg font-semibold text-gray-900 group-hover:text-blue-600 dark:text-gray-100"
                    >
                        {{ post.title }}
                    </h2>
                    <p
                        v-if="post.excerpt"
                        class="mt-2 line-clamp-2 text-sm text-gray-500 dark:text-gray-400"
                    >
                        {{ post.excerpt }}
                    </p>
                    <span
                        class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-blue-600 dark:text-blue-400"
                    >
                        {{ t('blog.read_more') }} <ArrowRight class="h-4 w-4" />
                    </span>
                </div>
            </Link>
        </div>
        <p v-else class="mt-10 text-center text-gray-400 dark:text-gray-500">
            {{ t('blog.empty') }}
        </p>
    </div>
</template>
