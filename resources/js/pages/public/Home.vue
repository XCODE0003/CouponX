<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BadgeCheck, Clock, Shield, Sparkles } from '@lucide/vue';
import CategoryCard from '@/components/public/CategoryCard.vue';
import CouponCard from '@/components/public/CouponCard.vue';
import HotDeals from '@/components/public/HotDeals.vue';
import Newsletter from '@/components/public/Newsletter.vue';
import Partners from '@/components/public/Partners.vue';
import StoreCard from '@/components/public/StoreCard.vue';
import { useI18n } from '@/composables/useI18n';
import type { CategoryData, CouponData, StoreCardData } from '@/types/public';

defineProps<{
    topStores: StoreCardData[];
    topCoupons: CouponData[];
    categories: CategoryData[];
}>();

const { t, locale } = useI18n();
</script>

<template>
    <Head :title="t('hero.title_1')" />

    <!-- Hero -->
    <section
        class="relative overflow-hidden bg-gradient-to-b from-blue-50/60 to-white"
    >
        <div
            class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24"
        >
            <div>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-blue-100/70 px-3 py-1 text-xs font-medium text-blue-700"
                >
                    <Sparkles class="h-3.5 w-3.5" /> {{ t('hero.badge') }}
                </span>
                <h1
                    class="mt-5 text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl"
                >
                    {{ t('hero.title_1') }}
                    <span class="text-blue-600">{{ t('hero.title_2') }}</span>
                </h1>
                <p class="mt-5 max-w-lg text-base text-gray-500">
                    {{ t('hero.subtitle') }}
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <Link
                        :href="`/${locale}/stores`"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >
                        {{ t('hero.cta_explore') }}
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                    <Link
                        :href="`/${locale}/categories`"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        {{ t('hero.cta_how') }}
                    </Link>
                </div>
                <div
                    class="mt-7 flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-500"
                >
                    <span class="inline-flex items-center gap-1.5"
                        ><BadgeCheck class="h-4 w-4 text-emerald-500" />
                        {{ t('hero.feature_verified') }}</span
                    >
                    <span class="inline-flex items-center gap-1.5"
                        ><Shield class="h-4 w-4 text-blue-500" />
                        {{ t('hero.feature_no_reg') }}</span
                    >
                    <span class="inline-flex items-center gap-1.5"
                        ><Clock class="h-4 w-4 text-violet-500" />
                        {{ t('hero.feature_updated') }}</span
                    >
                </div>
            </div>

            <!-- Animated hot-deals card -->
            <div class="hidden lg:block">
                <HotDeals
                    :coupons="topCoupons"
                    :view-all-url="`/${locale}/stores`"
                />
            </div>
        </div>
    </section>

    <!-- Our partners -->
    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="text-center">
            <p
                class="text-xs font-semibold tracking-wide text-blue-600 uppercase"
            >
                {{ t('partners.eyebrow') }}
            </p>
            <h2 class="mt-2 text-2xl font-bold text-gray-900 sm:text-3xl">
                {{ t('partners.title') }}
            </h2>
            <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500">
                {{ t('partners.subtitle') }}
            </p>
        </div>
        <Partners :stores="topStores" class="mt-10" />
        <div class="mt-8 text-center">
            <Link
                :href="`/${locale}/stores`"
                class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 hover:underline"
            >
                {{ t('partners.view_all') }} <ArrowRight class="h-4 w-4" />
            </Link>
        </div>
    </section>

    <!-- Top stores -->
    <section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-5 flex items-end justify-between">
            <div>
                <p
                    class="text-xs font-semibold tracking-wide text-gray-400 uppercase"
                >
                    {{ t('sections.popular_stores') }}
                </p>
                <h2 class="text-2xl font-bold text-gray-900">
                    {{ t('sections.browse_top_stores') }}
                </h2>
            </div>
            <Link
                :href="`/${locale}/stores`"
                class="text-sm font-semibold text-blue-600 hover:underline"
                >{{ t('sections.view_all') }}</Link
            >
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <StoreCard
                v-for="store in topStores"
                :key="store.id"
                :store="store"
            />
        </div>
    </section>

    <!-- Top coupons -->
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-5 flex items-end justify-between">
            <div>
                <p
                    class="text-xs font-semibold tracking-wide text-gray-400 uppercase"
                >
                    {{ t('sections.top_coupons') }}
                </p>
                <h2 class="text-2xl font-bold text-gray-900">
                    {{ t('sections.todays_best') }}
                </h2>
            </div>
        </div>
        <div class="space-y-3">
            <CouponCard
                v-for="coupon in topCoupons"
                :key="coupon.id"
                :coupon="coupon"
                show-store
            />
        </div>
    </section>

    <!-- Categories -->
    <section
        v-if="categories.length"
        class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8"
    >
        <h2 class="mb-5 text-2xl font-bold text-gray-900">
            {{ t('sections.popular_categories') }}
        </h2>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-8">
            <CategoryCard
                v-for="category in categories"
                :key="category.id"
                :category="category"
            />
        </div>
    </section>

    <!-- Newsletter -->
    <section
        id="newsletter"
        class="mx-auto max-w-7xl scroll-mt-20 px-4 py-12 sm:px-6 lg:px-8"
    >
        <Newsletter />
    </section>
</template>
