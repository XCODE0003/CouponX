<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Star } from '@lucide/vue';
import { useI18n } from '@/composables/useI18n';
import type { StoreCardData } from '@/types/public';
import StoreLogo from './StoreLogo.vue';

defineProps<{ store: StoreCardData }>();

const { t } = useI18n();
</script>

<template>
    <Link
        :href="store.url"
        class="group flex flex-col items-center gap-3 rounded-2xl border border-gray-100 bg-white p-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
    >
        <StoreLogo :name="store.name" :logo="store.logo" size="lg" />
        <div class="min-w-0">
            <h3
                class="truncate font-semibold text-gray-900 group-hover:text-blue-600"
            >
                {{ store.name }}
            </h3>
            <p
                v-if="store.cashback_value"
                class="text-xs font-medium text-emerald-600"
            >
                {{ store.cashback_value }}
            </p>
        </div>
        <div class="flex items-center gap-3 text-xs text-gray-400">
            <span v-if="store.rating" class="inline-flex items-center gap-1">
                <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />{{
                    store.rating.toFixed(1)
                }}
            </span>
            <span v-if="store.coupons_count !== null">{{
                t('stores.coupons_count', { count: store.coupons_count })
            }}</span>
        </div>
    </Link>
</template>
