<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import CategoryCard from '@/components/public/CategoryCard.vue';
import CouponCard from '@/components/public/CouponCard.vue';
import SearchBar from '@/components/public/SearchBar.vue';
import StoreCard from '@/components/public/StoreCard.vue';
import { useI18n } from '@/composables/useI18n';
import type { CategoryData, CouponData, StoreCardData } from '@/types/public';

const props = defineProps<{
    term: string;
    stores: StoreCardData[];
    coupons: CouponData[];
    categories: CategoryData[];
}>();

const { t } = useI18n();

const hasResults = computed(
    () =>
        props.stores.length > 0 ||
        props.coupons.length > 0 ||
        props.categories.length > 0,
);
</script>

<template>
    <Head :title="t('search.title')" />

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-xl">
            <SearchBar :initial="term" />
        </div>

        <h1 v-if="term" class="mt-8 text-2xl font-bold text-gray-900">
            {{ t('search.results_for', { term }) }}
        </h1>

        <p v-if="!term" class="mt-10 text-center text-gray-400">
            {{ t('search.prompt') }}
        </p>
        <p v-else-if="!hasResults" class="mt-10 text-center text-gray-400">
            {{ t('search.empty') }}
        </p>

        <div v-else :key="term" class="search-results">
            <section v-if="stores.length" class="mt-8">
                <h2 class="mb-4 text-lg font-bold text-gray-900">
                    {{ t('search.stores') }}
                </h2>
                <div
                    class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5"
                >
                    <StoreCard
                        v-for="store in stores"
                        :key="store.id"
                        :store="store"
                    />
                </div>
            </section>

            <section v-if="categories.length" class="mt-8">
                <h2 class="mb-4 text-lg font-bold text-gray-900">
                    {{ t('search.categories') }}
                </h2>
                <div
                    class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
                >
                    <CategoryCard
                        v-for="category in categories"
                        :key="category.id"
                        :category="category"
                    />
                </div>
            </section>

            <section v-if="coupons.length" class="mt-8">
                <h2 class="mb-4 text-lg font-bold text-gray-900">
                    {{ t('search.coupons') }}
                </h2>
                <div class="space-y-3">
                    <CouponCard
                        v-for="coupon in coupons"
                        :key="coupon.id"
                        :coupon="coupon"
                        show-store
                    />
                </div>
            </section>
        </div>
    </div>
</template>

<style scoped>
@keyframes searchIn {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}

.search-results {
    animation: searchIn 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@media (prefers-reduced-motion: reduce) {
    .search-results {
        animation: none;
    }
}
</style>
