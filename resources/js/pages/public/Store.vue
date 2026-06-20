<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BadgeCheck, ShieldCheck, Star } from '@lucide/vue';
import { computed, ref } from 'vue';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import CouponCard from '@/components/public/CouponCard.vue';
import Newsletter from '@/components/public/Newsletter.vue';
import StoreLogo from '@/components/public/StoreLogo.vue';
import { useI18n } from '@/composables/useI18n';
import type { CategoryData, CouponData, StoreFullData } from '@/types/public';

const props = defineProps<{
    store: StoreFullData;
    coupons: CouponData[];
    counts: { all: number; code: number; deal: number; sale: number };
    similar: StoreFullData[];
    storeCategories: CategoryData[];
}>();

const { t, locale } = useI18n();

type Tab = 'all' | 'code' | 'deal' | 'sale';
const activeTab = ref<Tab>('all');

const tabs = computed(() =>
    (
        [
            { key: 'all', label: t('store.tabs_all'), count: props.counts.all },
            {
                key: 'code',
                label: t('store.tabs_codes'),
                count: props.counts.code,
            },
            {
                key: 'deal',
                label: t('store.tabs_deals'),
                count: props.counts.deal,
            },
            {
                key: 'sale',
                label: t('store.tabs_sales'),
                count: props.counts.sale,
            },
        ] as { key: Tab; label: string; count: number }[]
    ).filter((tab) => tab.key === 'all' || tab.count > 0),
);

const visibleCoupons = computed(() =>
    activeTab.value === 'all'
        ? props.coupons
        : props.coupons.filter((c) => c.type === activeTab.value),
);
</script>

<template>
    <Head :title="store.meta_title || store.name" />

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <Breadcrumbs
            :items="[
                { label: t('nav.stores'), href: `/${locale}/stores` },
                { label: store.name },
            ]"
            class="mb-5"
        />

        <!-- Store header -->
        <div
            class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex items-start gap-4">
                    <StoreLogo
                        :name="store.name"
                        :logo="store.logo"
                        :logo-dark="store.logo_dark"
                        size="lg"
                    />
                    <div>
                        <div class="flex items-center gap-3">
                            <h1
                                class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                            >
                                {{ store.name }}
                            </h1>
                            <span
                                v-if="store.rating"
                                class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-sm font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400"
                            >
                                <Star
                                    class="h-3.5 w-3.5 fill-emerald-500 text-emerald-500 dark:text-emerald-400"
                                />{{ store.rating.toFixed(1) }}
                            </span>
                            <span
                                v-if="store.rating"
                                class="text-sm text-gray-400 dark:text-gray-500"
                                >{{ t('store.rating_excellent') }}</span
                            >
                        </div>
                        <p
                            v-if="store.description"
                            class="mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{ store.description }}
                        </p>
                        <div
                            class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            <span class="inline-flex items-center gap-1.5"
                                ><BadgeCheck
                                    class="h-4 w-4 text-emerald-500 dark:text-emerald-400"
                                />
                                {{ t('store.feature_verified') }}</span
                            >
                            <span class="inline-flex items-center gap-1.5"
                                ><ShieldCheck class="h-4 w-4 text-violet-500" />
                                {{ t('store.feature_no_hidden') }}</span
                            >
                        </div>
                    </div>
                </div>
                <div class="text-center lg:text-right">
                    <a
                        :href="store.go_url"
                        target="_blank"
                        rel="nofollow noopener sponsored"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >
                        {{ t('store.go_to_store') }}
                        <ArrowRight class="h-4 w-4" />
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Coupons column -->
            <div class="lg:col-span-2">
                <div
                    class="mb-4 flex flex-wrap gap-2 rounded-xl border border-gray-100 bg-white p-1.5 dark:border-gray-800 dark:bg-gray-900"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition"
                        :class="
                            activeTab === tab.key
                                ? 'bg-blue-600 text-white'
                                : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800'
                        "
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                        <span
                            class="rounded px-1.5 text-xs"
                            :class="
                                activeTab === tab.key
                                    ? 'bg-white/20'
                                    : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'
                            "
                            >{{ tab.count }}</span
                        >
                    </button>
                </div>

                <h2
                    class="mb-1 text-lg font-bold text-gray-900 dark:text-gray-100"
                >
                    {{ t('store.best_codes', { store: store.name }) }}
                </h2>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ t('store.best_codes_sub') }}
                </p>

                <div v-if="visibleCoupons.length" class="space-y-3">
                    <CouponCard
                        v-for="coupon in visibleCoupons"
                        :key="coupon.id"
                        :coupon="coupon"
                    />
                </div>
                <p
                    v-else
                    class="rounded-2xl border border-dashed border-gray-200 bg-white p-8 text-center text-sm text-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-500"
                >
                    {{ t('store.no_coupons') }}
                </p>

                <!-- SEO text -->
                <div
                    v-if="store.about"
                    class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900"
                >
                    <h2
                        class="text-base font-semibold text-gray-900 dark:text-gray-100"
                    >
                        {{ store.name }}
                    </h2>
                    <p
                        class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400"
                    >
                        {{ store.about }}
                    </p>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="space-y-6">
                <div
                    class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <h3
                        class="mb-4 text-base font-bold text-gray-900 dark:text-gray-100"
                    >
                        {{ t('store.about') }}
                    </h3>
                    <dl class="space-y-3 text-sm">
                        <div v-if="store.countries?.length">
                            <dt class="text-gray-400 dark:text-gray-500">
                                {{ t('store.supported_countries') }}
                            </dt>
                            <dd
                                class="font-medium text-gray-900 dark:text-gray-100"
                            >
                                {{ store.countries.join(', ') }}
                            </dd>
                        </div>
                    </dl>
                    <a
                        :href="store.go_url"
                        target="_blank"
                        rel="nofollow noopener sponsored"
                        class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300"
                    >
                        {{ t('store.go_to_store') }}
                        <ArrowRight class="h-4 w-4" />
                    </a>
                </div>

                <div
                    v-if="storeCategories.length"
                    class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <h3
                        class="mb-4 text-base font-bold text-gray-900 dark:text-gray-100"
                    >
                        {{ t('store.categories') }}
                    </h3>
                    <ul class="space-y-2">
                        <li v-for="cat in storeCategories" :key="cat.id">
                            <Link
                                :href="cat.url"
                                class="flex items-center justify-between text-sm text-gray-600 hover:text-blue-600 dark:text-gray-300"
                            >
                                <span>{{ cat.name }}</span>
                                <span
                                    v-if="cat.stores_count !== null"
                                    class="text-xs text-gray-400 dark:text-gray-500"
                                    >{{ cat.stores_count }}</span
                                >
                            </Link>
                        </li>
                    </ul>
                </div>

                <div
                    v-if="similar.length"
                    class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <h3
                        class="mb-4 text-base font-bold text-gray-900 dark:text-gray-100"
                    >
                        {{ t('store.similar') }}
                    </h3>
                    <ul class="space-y-3">
                        <li v-for="s in similar" :key="s.id">
                            <Link
                                :href="s.url"
                                class="flex items-center gap-3 hover:opacity-80"
                            >
                                <StoreLogo
                                    :name="s.name"
                                    :logo="s.logo"
                                    :logo-dark="s.logo_dark"
                                    size="sm"
                                />
                                <span class="min-w-0">
                                    <span
                                        class="block truncate text-sm font-medium text-gray-900 dark:text-gray-100"
                                        >{{ s.name }}</span
                                    >
                                </span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>

        <div class="mt-12">
            <Newsletter />
        </div>
    </div>
</template>
