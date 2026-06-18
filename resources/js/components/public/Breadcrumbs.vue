<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { useI18n } from '@/composables/useI18n';

export interface Crumb {
    label: string;
    href?: string;
}

defineProps<{ items: Crumb[] }>();

const { t, locale } = useI18n();
</script>

<template>
    <nav
        class="flex flex-wrap items-center gap-1 text-sm text-gray-400"
        aria-label="Breadcrumb"
    >
        <Link :href="`/${locale}`" class="hover:text-blue-600">{{
            t('breadcrumb_home')
        }}</Link>
        <template v-for="(crumb, i) in items" :key="i">
            <ChevronRight class="h-4 w-4" />
            <Link
                v-if="crumb.href"
                :href="crumb.href"
                class="hover:text-blue-600"
                >{{ crumb.label }}</Link
            >
            <span v-else class="font-medium text-gray-600">{{
                crumb.label
            }}</span>
        </template>
    </nav>
</template>
