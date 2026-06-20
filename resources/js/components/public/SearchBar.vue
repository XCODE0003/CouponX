<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from '@/composables/useI18n';

const props = withDefaults(defineProps<{ initial?: string }>(), {
    initial: '',
});

const { t, locale } = useI18n();
const query = ref(props.initial);

function submit(): void {
    const term = query.value.trim();

    if (term === '') {
        return;
    }

    // When already on the search page, update results in place (partial reload)
    // so the results block re-animates smoothly instead of a full-page reload.
    const onSearchPage = window.location.pathname.endsWith('/search');

    router.get(
        `/${locale.value}/search`,
        { q: term },
        onSearchPage
            ? {
                  preserveState: true,
                  preserveScroll: true,
                  replace: true,
                  only: ['stores', 'coupons', 'categories', 'term'],
              }
            : { preserveState: false },
    );
}
</script>

<template>
    <form class="relative w-full" role="search" @submit.prevent="submit">
        <Search
            class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500"
        />
        <input
            v-model="query"
            type="search"
            :placeholder="t('nav.search_placeholder')"
            class="w-full rounded-full border border-gray-200 bg-gray-50 py-2 pr-4 pl-9 text-sm text-gray-900 transition outline-none focus:border-blue-300 focus:bg-white focus:ring-2 focus:ring-blue-100 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100"
        />
    </form>
</template>
