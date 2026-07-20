<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import type { CategoryData } from '@/types/public';

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
interface ActiveFilters {
    sort: string;
    discount: number;
    type: string[];
    delivery: string[];
}

const props = defineProps<{
    baseUrl: string;
    facets: {
        discounts: DiscountFacet[];
        types: TypeFacet[];
        deliveries: DeliveryFacet[];
    };
    subcategories: CategoryData[];
    active: ActiveFilters;
}>();

const { t } = useI18n();

const sort = ref(props.active.sort || 'popular');
const discount = ref(props.active.discount || 0);
const types = ref<string[]>([...props.active.type]);
const deliveries = ref<string[]>([...props.active.delivery]);
const showAllDeliveries = ref(false);

// Keep local state in sync if the server echoes different active filters.
watch(
    () => props.active,
    (a) => {
        sort.value = a.sort || 'popular';
        discount.value = a.discount || 0;
        types.value = [...a.type];
        deliveries.value = [...a.delivery];
    },
);

function apply(): void {
    const params: Record<string, string | string[] | number> = {};

    if (sort.value && sort.value !== 'popular') {
        params.sort = sort.value;
    }

    if (discount.value) {
        params.discount = discount.value;
    }

    if (types.value.length) {
        params.type = types.value;
    }

    if (deliveries.value.length) {
        params.delivery = deliveries.value;
    }

    router.get(props.baseUrl, params, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function toggle(kind: 'type' | 'delivery', value: string): void {
    const list = kind === 'type' ? types : deliveries;
    list.value = list.value.includes(value)
        ? list.value.filter((v) => v !== value)
        : [...list.value, value];
    apply();
}

function setDiscount(value: number): void {
    discount.value = discount.value === value ? 0 : value;
    apply();
}

function reset(): void {
    sort.value = 'popular';
    discount.value = 0;
    types.value = [];
    deliveries.value = [];
    router.get(
        props.baseUrl,
        {},
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

const hasActive = () =>
    discount.value > 0 ||
    types.value.length > 0 ||
    deliveries.value.length > 0 ||
    sort.value !== 'popular';
</script>

<template>
    <aside
        class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900"
    >
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                {{ t('filters.title') }}
            </h2>
            <button
                v-if="hasActive()"
                type="button"
                class="text-sm font-semibold text-blue-600 hover:underline dark:text-blue-400"
                @click="reset"
            >
                {{ t('filters.reset') }}
            </button>
        </div>

        <!-- Sort -->
        <div class="mb-5">
            <p
                class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100"
            >
                {{ t('filters.sort') }}
            </p>
            <select
                v-model="sort"
                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                @change="apply"
            >
                <option value="popular">{{ t('filters.sort_popular') }}</option>
                <option value="new">{{ t('filters.sort_new') }}</option>
                <option value="expiring">
                    {{ t('filters.sort_expiring') }}
                </option>
            </select>
        </div>

        <!-- Discount -->
        <div class="mb-5">
            <p
                class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100"
            >
                {{ t('filters.discount') }}
            </p>
            <ul class="space-y-1.5">
                <li v-for="bucket in facets.discounts" :key="bucket.value">
                    <label
                        class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
                    >
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-400 dark:border-gray-700 dark:text-blue-400"
                            :checked="discount === bucket.value"
                            @change="setDiscount(bucket.value)"
                        />
                        <span>{{
                            t('filters.from_and_up', { percent: bucket.value })
                        }}</span>
                        <span
                            class="ml-auto text-xs text-gray-400 dark:text-gray-500"
                            >({{ bucket.count }})</span
                        >
                    </label>
                </li>
            </ul>
        </div>

        <!-- Offer type -->
        <div class="mb-5">
            <p
                class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100"
            >
                {{ t('filters.offer_type') }}
            </p>
            <ul class="space-y-1.5">
                <li v-for="type in facets.types" :key="type.key">
                    <label
                        class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
                    >
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-400 dark:border-gray-700 dark:text-blue-400"
                            :checked="types.includes(type.key)"
                            @change="toggle('type', type.key)"
                        />
                        <span>{{ type.label }}</span>
                        <span
                            class="ml-auto text-xs text-gray-400 dark:text-gray-500"
                            >({{ type.count }})</span
                        >
                    </label>
                </li>
            </ul>
        </div>

        <!-- Subcategories -->
        <div v-if="subcategories.length" class="mb-5">
            <p
                class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100"
            >
                {{ t('filters.subcategories') }}
            </p>
            <ul class="space-y-1.5">
                <li v-for="sub in subcategories" :key="sub.id">
                    <Link
                        :href="sub.url"
                        class="flex items-center gap-2 text-sm text-gray-600 hover:text-blue-600 dark:text-gray-300"
                    >
                        <span>{{ sub.name }}</span>
                        <span
                            v-if="sub.stores_count !== null"
                            class="ml-auto text-xs text-gray-400 dark:text-gray-500"
                            >({{ sub.stores_count }})</span
                        >
                    </Link>
                </li>
            </ul>
        </div>

        <!-- Delivery — hidden entirely until stores actually carry geo data,
             otherwise the section shows a heading over an empty list. -->
        <div v-if="facets.deliveries.length">
            <p
                class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100"
            >
                {{ t('filters.delivery') }}
            </p>
            <ul class="space-y-1.5">
                <li
                    v-for="(country, i) in facets.deliveries"
                    v-show="showAllDeliveries || i < 4"
                    :key="country.code"
                >
                    <label
                        class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
                    >
                        <input
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-400 dark:border-gray-700 dark:text-blue-400"
                            :checked="deliveries.includes(country.code)"
                            @change="toggle('delivery', country.code)"
                        />
                        <span>{{ country.label }}</span>
                        <span
                            class="ml-auto text-xs text-gray-400 dark:text-gray-500"
                            >({{ country.count }})</span
                        >
                    </label>
                </li>
            </ul>
            <button
                v-if="facets.deliveries.length > 4"
                type="button"
                class="mt-2 text-sm font-semibold text-blue-600 hover:underline dark:text-blue-400"
                @click="showAllDeliveries = !showAllDeliveries"
            >
                {{ t('filters.show_all') }}
            </button>
        </div>
    </aside>
</template>
