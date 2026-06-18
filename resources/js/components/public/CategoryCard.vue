<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import type { CategoryData } from '@/types/public';
import CategoryIcon from './CategoryIcon.vue';

defineProps<{ category: CategoryData }>();

const { t } = useI18n();
</script>

<template>
    <Link
        :href="category.url"
        class="group flex flex-col items-center gap-3 rounded-2xl border border-gray-100 bg-white p-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
    >
        <span
            class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white"
        >
            <CategoryIcon :name="category.icon" class="h-6 w-6" />
        </span>
        <span class="font-semibold text-gray-900 group-hover:text-blue-600">{{
            category.name
        }}</span>
        <span
            v-if="category.stores_count !== null"
            class="text-xs text-gray-400"
        >
            {{ t('categories.stores_count', { count: category.stores_count }) }}
        </span>
    </Link>
</template>
