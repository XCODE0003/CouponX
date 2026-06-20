<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import StoreCard from '@/components/public/StoreCard.vue';
import { useI18n } from '@/composables/useI18n';
import type { CategoryData, Pagination, StoreCardData } from '@/types/public';

const props = defineProps<{
    stores: StoreCardData[];
    categories: CategoryData[];
    pagination: Pagination;
    activeCategory: string | null;
}>();

const { t, locale } = useI18n();

function filterBy(slug: string | null): void {
    router.get(`/${locale.value}/stores`, slug ? { category: slug } : {}, {
        preserveScroll: true,
    });
}

function goToPage(page: number): void {
    router.get(
        `/${locale.value}/stores`,
        {
            ...(props.activeCategory ? { category: props.activeCategory } : {}),
            page,
        },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="t('stores.title')" />

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <Breadcrumbs :items="[{ label: t('nav.stores') }]" class="mb-4" />
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            {{ t('stores.title') }}
        </h1>
        <p class="mt-1 text-gray-500 dark:text-gray-400">
            {{ t('stores.subtitle') }}
        </p>

        <!-- Category filter chips -->
        <div class="mt-6 flex flex-wrap gap-2">
            <button
                type="button"
                class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                :class="
                    !activeCategory
                        ? 'bg-blue-600 text-white'
                        : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'
                "
                @click="filterBy(null)"
            >
                {{ t('stores.filter_all') }}
            </button>
            <button
                v-for="cat in categories"
                :key="cat.id"
                type="button"
                class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                :class="
                    activeCategory === cat.slug
                        ? 'bg-blue-600 text-white'
                        : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'
                "
                @click="filterBy(cat.slug)"
            >
                {{ cat.name }}
            </button>
        </div>

        <div
            v-if="stores.length"
            class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5"
        >
            <StoreCard v-for="store in stores" :key="store.id" :store="store" />
        </div>
        <p v-else class="mt-10 text-center text-gray-400 dark:text-gray-500">
            {{ t('stores.empty') }}
        </p>

        <!-- Pagination -->
        <div
            v-if="pagination.last > 1"
            class="mt-10 flex items-center justify-center gap-1"
        >
            <button
                v-for="p in pagination.last"
                :key="p"
                type="button"
                class="h-9 w-9 rounded-lg text-sm font-medium transition"
                :class="
                    p === pagination.current
                        ? 'bg-blue-600 text-white'
                        : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'
                "
                @click="goToPage(p)"
            >
                {{ p }}
            </button>
        </div>

        <p class="sr-only">
            <Link :href="`/${locale}/categories`">{{
                t('nav.categories')
            }}</Link>
        </p>
    </div>
</template>
