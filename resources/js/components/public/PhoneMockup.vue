<script setup lang="ts">
import { Search, Wifi } from '@lucide/vue';
import type { CategoryData, StoreCardData } from '@/types/public';
import CategoryIcon from './CategoryIcon.vue';

withDefaults(
    defineProps<{
        stores?: StoreCardData[];
        categories?: CategoryData[];
        greeting?: string;
        subtitle?: string;
        searchPlaceholder?: string;
        topStoresLabel?: string;
        topCategoriesLabel?: string;
        viewAllLabel?: string;
    }>(),
    {
        stores: () => [],
        categories: () => [],
        greeting: 'Hello! 👋',
        subtitle: 'Find the best deals and save more',
        searchPlaceholder: 'Search stores or coupons...',
        topStoresLabel: 'Top Stores',
        topCategoriesLabel: 'Top Categories',
        viewAllLabel: 'View all',
    },
);

const storeTints = ['bg-amber-50', 'bg-orange-50', 'bg-gray-100', 'bg-blue-50'];
</script>

<template>
    <div class="phone-float relative mx-auto w-[300px] select-none">
        <!-- Glow -->
        <div
            class="absolute -inset-6 -z-10 rounded-full bg-blue-200/40 blur-3xl"
        ></div>

        <!-- Device -->
        <div
            class="relative rounded-[2.6rem] border-[10px] border-gray-900 bg-gray-900 shadow-2xl"
        >
            <!-- Notch -->
            <div
                class="absolute top-0 left-1/2 z-10 h-6 w-32 -translate-x-1/2 rounded-b-2xl bg-gray-900"
            ></div>

            <!-- Screen -->
            <div
                class="overflow-hidden rounded-[1.9rem] bg-gradient-to-b from-white to-blue-50/50 px-4 pt-3 pb-6"
            >
                <!-- Status bar -->
                <div
                    class="flex items-center justify-between text-[10px] font-semibold text-gray-900"
                >
                    <span>9:41</span>
                    <div class="flex items-center gap-1">
                        <span class="flex items-end gap-px">
                            <span
                                class="h-1 w-0.5 rounded-sm bg-gray-900"
                            ></span>
                            <span
                                class="h-1.5 w-0.5 rounded-sm bg-gray-900"
                            ></span>
                            <span
                                class="h-2 w-0.5 rounded-sm bg-gray-900"
                            ></span>
                            <span
                                class="h-2.5 w-0.5 rounded-sm bg-gray-900"
                            ></span>
                        </span>
                        <Wifi class="h-3 w-3" />
                        <span
                            class="ml-0.5 h-2.5 w-5 rounded-sm border border-gray-900 px-px"
                        >
                            <span
                                class="block h-full w-3/4 rounded-[1px] bg-gray-900"
                            ></span>
                        </span>
                    </div>
                </div>

                <!-- Greeting -->
                <div class="mt-4 flex items-start justify-between">
                    <div>
                        <p class="text-sm font-bold text-gray-900">
                            {{ greeting }}
                        </p>
                        <p
                            class="mt-0.5 text-[11px] leading-tight text-gray-500"
                        >
                            {{ subtitle }}
                        </p>
                    </div>
                    <div
                        class="h-9 w-9 shrink-0 rounded-full bg-gradient-to-br from-blue-200 to-violet-200"
                    ></div>
                </div>

                <!-- Search -->
                <div
                    class="phone-pop mt-3 flex items-center gap-2 rounded-xl bg-white px-3 py-2.5 shadow-sm"
                    style="animation-delay: 0.1s"
                >
                    <Search class="h-3.5 w-3.5 text-gray-300" />
                    <span class="truncate text-[11px] text-gray-300">{{
                        searchPlaceholder
                    }}</span>
                </div>

                <!-- Top stores -->
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-[11px] font-bold text-gray-900">{{
                        topStoresLabel
                    }}</span>
                    <span class="text-[10px] font-semibold text-blue-600">{{
                        viewAllLabel
                    }}</span>
                </div>
                <div class="mt-2 grid grid-cols-4 gap-2">
                    <div
                        v-for="(store, i) in stores.slice(0, 4)"
                        :key="store.id"
                        class="phone-pop rounded-xl bg-white p-1.5 text-center shadow-sm"
                        :style="{ animationDelay: `${0.2 + i * 0.08}s` }"
                    >
                        <div
                            class="mx-auto flex h-9 w-9 items-center justify-center rounded-lg text-xs font-bold text-gray-700"
                            :class="storeTints[i % storeTints.length]"
                        >
                            {{ store.name.charAt(0) }}
                        </div>
                        <span
                            class="mt-1 block truncate text-[8px] text-gray-500"
                            >{{ store.name }}</span
                        >
                    </div>
                </div>

                <!-- Top categories -->
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-[11px] font-bold text-gray-900">{{
                        topCategoriesLabel
                    }}</span>
                    <span class="text-[10px] font-semibold text-blue-600">{{
                        viewAllLabel
                    }}</span>
                </div>
                <div class="mt-2 grid grid-cols-4 gap-2">
                    <div
                        v-for="(category, i) in categories.slice(0, 4)"
                        :key="category.id"
                        class="phone-pop rounded-xl bg-white p-1.5 text-center shadow-sm"
                        :style="{ animationDelay: `${0.5 + i * 0.08}s` }"
                    >
                        <span
                            class="mx-auto flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600"
                        >
                            <CategoryIcon
                                :name="category.icon"
                                class="h-4 w-4"
                            />
                        </span>
                        <span
                            class="mt-1 block truncate text-[8px] text-gray-500"
                            >{{ category.name }}</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
