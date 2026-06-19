<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronDown, ChevronRight, Percent, Store, Ticket } from '@lucide/vue';
import { computed, ref } from 'vue';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import CategoryIcon from '@/components/public/CategoryIcon.vue';
import CouponCard from '@/components/public/CouponCard.vue';
import Filters from '@/components/public/Filters.vue';
import Newsletter from '@/components/public/Newsletter.vue';
import Reveal from '@/components/public/Reveal.vue';
import StoreCard from '@/components/public/StoreCard.vue';
import StoreLogo from '@/components/public/StoreLogo.vue';
import { useI18n } from '@/composables/useI18n';
import type {
    BlogPostData,
    CategoryData,
    CouponData,
    StoreCardData,
} from '@/types/public';

interface CategoryDetail extends CategoryData {
    description: string | null;
    max_discount: number | null;
}
interface CarouselStore extends StoreCardData {
    max_discount: number | null;
}
interface DiscountFacet {
    value: number;
    count: number;
}
interface TypeFacet {
    key: string;
    label: string;
    count: number;
}
interface DeliveryFacet {
    code: string;
    label: string;
    count: number;
}

const props = defineProps<{
    category: CategoryDetail;
    stores: CarouselStore[];
    coupons: CouponData[];
    subcategories: CategoryData[];
    posts: BlogPostData[];
    facets: {
        discounts: DiscountFacet[];
        types: TypeFacet[];
        deliveries: DeliveryFacet[];
    };
    active: {
        sort: string;
        discount: number;
        type: string[];
        delivery: string[];
    };
    counts: { stores: number; coupons: number; articles: number };
}>();

const { t, locale } = useI18n();

type Tab = 'coupons' | 'stores' | 'articles';
const tab = ref<Tab>('coupons');

const tabs = computed(() => [
    {
        key: 'stores' as Tab,
        label: t('cat.tab_stores'),
        count: props.counts.stores,
    },
    {
        key: 'coupons' as Tab,
        label: t('cat.tab_coupons'),
        count: props.counts.coupons,
    },
    {
        key: 'articles' as Tab,
        label: t('cat.tab_articles'),
        count: props.counts.articles,
    },
]);

const visibleCount = ref(5);
const visibleCoupons = computed(() =>
    props.coupons.slice(0, visibleCount.value),
);

const scroller = ref<HTMLElement | null>(null);
function scrollRight(): void {
    scroller.value?.scrollBy({ left: 260, behavior: 'smooth' });
}

function formatDate(iso: string | null): string {
    return iso ? new Date(iso).toLocaleDateString() : '';
}
</script>

<template>
    <Head :title="category.name" />

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <Breadcrumbs
            :items="[
                { label: t('nav.categories'), href: `/${locale}/categories` },
                { label: category.name },
            ]"
            class="mb-4"
        />

        <!-- Hero header -->
        <Reveal>
            <div
                class="overflow-hidden rounded-2xl border border-gray-100 bg-gradient-to-r from-blue-50/70 to-white p-6 shadow-sm sm:p-8"
            >
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <span
                        class="flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm"
                    >
                        <CategoryIcon :name="category.icon" class="h-12 w-12" />
                    </span>
                    <div class="flex-1">
                        <h1
                            class="text-3xl font-extrabold tracking-tight text-gray-900"
                        >
                            {{ category.name }}
                        </h1>
                        <p
                            v-if="category.description"
                            class="mt-2 max-w-2xl text-sm text-gray-500"
                        >
                            {{ category.description }}
                        </p>
                        <div
                            class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-600"
                        >
                            <span class="inline-flex items-center gap-1.5">
                                <Store class="h-4 w-4 text-blue-500" />
                                {{
                                    t('cat.stat_stores', {
                                        count: counts.stores,
                                    })
                                }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <Ticket class="h-4 w-4 text-violet-500" />
                                {{
                                    t('cat.stat_coupons', {
                                        count: counts.coupons,
                                    })
                                }}
                            </span>
                            <span
                                v-if="category.max_discount"
                                class="inline-flex items-center gap-1.5"
                            >
                                <Percent class="h-4 w-4 text-emerald-500" />
                                {{
                                    t('cat.stat_discount', {
                                        percent: category.max_discount,
                                    })
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </Reveal>

        <!-- Tabs -->
        <div class="mt-6 flex flex-wrap gap-1 border-b border-gray-100">
            <button
                v-for="item in tabs"
                :key="item.key"
                type="button"
                class="-mb-px border-b-2 px-4 py-3 text-sm font-medium transition"
                :class="
                    tab === item.key
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-900'
                "
                @click="tab = item.key"
            >
                {{ item.label }}
                <span class="ml-1 text-xs text-gray-400">{{ item.count }}</span>
            </button>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-4">
            <!-- Filters sidebar -->
            <div class="lg:col-span-1">
                <Filters
                    :base-url="category.url"
                    :facets="facets"
                    :subcategories="subcategories"
                    :active="active"
                />
            </div>

            <!-- Main column -->
            <div class="lg:col-span-3">
                <!-- Stores carousel (coupons & stores views) -->
                <section
                    v-if="tab !== 'articles' && stores.length"
                    class="mb-8"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900">
                            {{
                                t('cat.popular_stores', {
                                    category: category.name,
                                })
                            }}
                        </h2>
                        <div class="flex items-center gap-3">
                            <Link
                                :href="`/${locale}/stores?category=${category.slug}`"
                                class="text-sm font-semibold text-blue-600 hover:underline"
                            >
                                {{ t('cat.see_all') }}
                            </Link>
                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50"
                                @click="scrollRight"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <div ref="scroller" class="flex gap-4 overflow-x-auto pb-2">
                        <Link
                            v-for="store in stores"
                            :key="store.id"
                            :href="store.url"
                            class="group flex w-44 shrink-0 flex-col items-center rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm transition hover:shadow-md"
                        >
                            <StoreLogo
                                :name="store.name"
                                :logo="store.logo"
                                size="lg"
                            />
                            <p
                                v-if="store.max_discount"
                                class="mt-3 text-sm font-semibold text-emerald-600"
                            >
                                {{
                                    t('cat.up_to_off', {
                                        percent: store.max_discount,
                                    })
                                }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{
                                    t('cat.coupons_n', {
                                        count: store.coupons_count ?? 0,
                                    })
                                }}
                            </p>
                            <span
                                class="mt-3 w-full rounded-lg bg-blue-50 px-4 py-1.5 text-sm font-semibold text-blue-600 transition group-hover:bg-blue-100"
                            >
                                {{ t('cat.view') }}
                            </span>
                        </Link>
                    </div>
                </section>

                <!-- Coupons view -->
                <section v-if="tab === 'coupons'">
                    <h2 class="mb-4 text-xl font-bold text-gray-900">
                        {{ t('cat.best_coupons', { category: category.name }) }}
                    </h2>
                    <div v-if="visibleCoupons.length" class="space-y-3">
                        <CouponCard
                            v-for="coupon in visibleCoupons"
                            :key="coupon.id"
                            :coupon="coupon"
                            show-store
                        />
                    </div>
                    <p
                        v-else
                        class="rounded-2xl border border-dashed border-gray-200 bg-white p-8 text-center text-sm text-gray-400"
                    >
                        {{ t('cat.no_results') }}
                    </p>
                    <div
                        v-if="coupons.length > visibleCount"
                        class="mt-6 text-center"
                    >
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-6 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                            @click="visibleCount += 5"
                        >
                            {{ t('cat.load_more') }}
                            <ChevronDown class="h-4 w-4" />
                        </button>
                    </div>
                </section>

                <!-- Stores grid view -->
                <section v-else-if="tab === 'stores'">
                    <div
                        v-if="stores.length"
                        class="grid grid-cols-2 gap-4 sm:grid-cols-3"
                    >
                        <StoreCard
                            v-for="store in stores"
                            :key="store.id"
                            :store="store"
                        />
                    </div>
                    <p v-else class="text-center text-gray-400">
                        {{ t('cat.no_stores') }}
                    </p>
                </section>

                <!-- Articles view -->
                <section v-else>
                    <div
                        v-if="posts.length"
                        class="grid grid-cols-1 gap-5 sm:grid-cols-2"
                    >
                        <Link
                            v-for="post in posts"
                            :key="post.id"
                            :href="post.url"
                            class="group rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:shadow-md"
                        >
                            <p class="text-xs text-gray-400">
                                {{ formatDate(post.published_at) }}
                            </p>
                            <h3
                                class="mt-1 font-semibold text-gray-900 group-hover:text-blue-600"
                            >
                                {{ post.title }}
                            </h3>
                            <p
                                v-if="post.excerpt"
                                class="mt-2 line-clamp-2 text-sm text-gray-500"
                            >
                                {{ post.excerpt }}
                            </p>
                        </Link>
                    </div>
                    <p v-else class="text-center text-gray-400">
                        {{ t('cat.no_articles') }}
                    </p>
                </section>
            </div>
        </div>

        <div class="mt-12">
            <Newsletter />
        </div>
    </div>
</template>
