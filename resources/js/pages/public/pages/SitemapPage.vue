<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import Reveal from '@/components/public/Reveal.vue';
import { useI18n } from '@/composables/useI18n';
import type { BlogPostData, CategoryData, StoreCardData } from '@/types/public';

interface SitemapContent {
    title: string;
    stores: string;
    categories: string;
    blog: string;
    pages: string;
}

defineProps<{
    content: SitemapContent;
    stores: StoreCardData[];
    categories: CategoryData[];
    posts: BlogPostData[];
}>();

const { t, locale } = useI18n();

const staticPages = computed(() => [
    { label: t('footer.about_us'), href: `/${locale.value}/about` },
    { label: t('footer.contact'), href: `/${locale.value}/contact` },
    { label: t('footer.how_it_works'), href: `/${locale.value}/how-it-works` },
    { label: t('footer.faq'), href: `/${locale.value}/faq` },
    { label: t('footer.privacy'), href: `/${locale.value}/privacy` },
    { label: t('footer.terms'), href: `/${locale.value}/terms` },
    { label: t('nav.blog'), href: `/${locale.value}/blog` },
]);
</script>

<template>
    <Head :title="content.title" />

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <Breadcrumbs :items="[{ label: content.title }]" class="mb-5" />
        <h1
            class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-gray-100"
        >
            {{ content.title }}
        </h1>

        <div class="mt-8 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <Reveal>
                <h2
                    class="mb-3 text-sm font-bold tracking-wide text-gray-900 uppercase dark:text-gray-100"
                >
                    {{ content.pages }}
                </h2>
                <ul class="space-y-2 text-sm">
                    <li v-for="p in staticPages" :key="p.href">
                        <Link
                            :href="p.href"
                            class="text-gray-600 hover:text-blue-600 dark:text-gray-300"
                            >{{ p.label }}</Link
                        >
                    </li>
                </ul>
            </Reveal>

            <Reveal :delay="80">
                <h2
                    class="mb-3 text-sm font-bold tracking-wide text-gray-900 uppercase dark:text-gray-100"
                >
                    {{ content.categories }}
                </h2>
                <ul class="space-y-2 text-sm">
                    <li v-for="c in categories" :key="c.id">
                        <Link
                            :href="c.url"
                            class="text-gray-600 hover:text-blue-600 dark:text-gray-300"
                            >{{ c.name }}</Link
                        >
                    </li>
                </ul>
            </Reveal>

            <Reveal :delay="160" class="lg:col-span-2">
                <h2
                    class="mb-3 text-sm font-bold tracking-wide text-gray-900 uppercase dark:text-gray-100"
                >
                    {{ content.stores }}
                </h2>
                <ul class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                    <li v-for="s in stores" :key="s.id">
                        <Link
                            :href="s.url"
                            class="text-gray-600 hover:text-blue-600 dark:text-gray-300"
                            >{{ s.name }}</Link
                        >
                    </li>
                </ul>
            </Reveal>

            <Reveal v-if="posts.length" :delay="160" class="lg:col-span-4">
                <h2
                    class="mb-3 text-sm font-bold tracking-wide text-gray-900 uppercase dark:text-gray-100"
                >
                    {{ content.blog }}
                </h2>
                <ul
                    class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3"
                >
                    <li v-for="post in posts" :key="post.id">
                        <Link
                            :href="post.url"
                            class="text-gray-600 hover:text-blue-600 dark:text-gray-300"
                            >{{ post.title }}</Link
                        >
                    </li>
                </ul>
            </Reveal>
        </div>
    </div>
</template>
