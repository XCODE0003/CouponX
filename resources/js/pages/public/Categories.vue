<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import CategoryCard from '@/components/public/CategoryCard.vue';
import { useI18n } from '@/composables/useI18n';
import type { CategoryData } from '@/types/public';

defineProps<{ categories: CategoryData[] }>();

const { t } = useI18n();
</script>

<template>
    <Head :title="t('categories.title')" />

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <Breadcrumbs :items="[{ label: t('nav.categories') }]" class="mb-4" />
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
            {{ t('categories.title') }}
        </h1>
        <p class="mt-1 text-gray-500 dark:text-gray-400">
            {{ t('categories.subtitle') }}
        </p>

        <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <CategoryCard
                v-for="category in categories"
                :key="category.id"
                :category="category"
            />
        </div>
    </div>
</template>
