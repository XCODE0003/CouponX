<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import { useI18n } from '@/composables/useI18n';
import type { BlogPostData } from '@/types/public';

defineProps<{ post: BlogPostData; related: BlogPostData[] }>();

const { t, locale } = useI18n();

function formatDate(iso: string | null): string {
    return iso ? new Date(iso).toLocaleDateString() : '';
}
</script>

<template>
    <Head :title="post.meta_title || post.title" />

    <article class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
        <Breadcrumbs
            :items="[
                { label: t('nav.blog'), href: `/${locale}/blog` },
                { label: post.title },
            ]"
            class="mb-5"
        />

        <p class="text-sm text-gray-400">
            {{
                t('blog.published_on', { date: formatDate(post.published_at) })
            }}
            <span v-if="post.author"> · {{ post.author }}</span>
        </p>
        <h1
            class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl"
        >
            {{ post.title }}
        </h1>

        <img
            v-if="post.cover_image"
            :src="post.cover_image"
            :alt="post.title"
            class="mt-6 aspect-[16/9] w-full rounded-2xl object-cover"
        />

        <!--
          post.body is CMS rich-text authored by trusted admin/editor users and
          sanitized server-side by Filament's RichEditor on save. This is the
          designated trusted HTML path for the site.
        -->
        <div
            class="prose prose-blue mt-8 max-w-none text-gray-700"
            v-html="post.body"
        ></div>

        <section
            v-if="related.length"
            class="mt-12 border-t border-gray-100 pt-8"
        >
            <h2 class="mb-4 text-lg font-bold text-gray-900">
                {{ t('blog.related') }}
            </h2>
            <ul class="space-y-3">
                <li v-for="r in related" :key="r.id">
                    <Link
                        :href="r.url"
                        class="text-sm font-medium text-blue-600 hover:underline"
                        >{{ r.title }}</Link
                    >
                </li>
            </ul>
        </section>
    </article>
</template>
