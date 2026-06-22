<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import type { StoreCardData } from '@/types/public';

defineProps<{ stores: StoreCardData[] }>();

const { resolvedAppearance } = useAppearance();

// Track logos that failed to load so we can fall back to the store name.
const broken = reactive<Record<number, boolean>>({});

function logoFor(store: StoreCardData): string | null {
    return resolvedAppearance.value === 'dark' && store.logo_dark
        ? store.logo_dark
        : store.logo;
}
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-center gap-x-10 gap-y-7 sm:gap-x-14"
    >
        <Link
            v-for="(store, i) in stores"
            :key="store.id"
            :href="store.url"
            class="partner-pop group inline-flex items-center"
            :style="{ animationDelay: `${i * 0.06}s` }"
            :title="store.name"
        >
            <img
                v-if="logoFor(store) && !broken[store.id]"
                :src="logoFor(store)!"
                :alt="store.name"
                class="h-9 w-auto object-contain transition duration-300 group-hover:scale-105"
                loading="lazy"
                @error="broken[store.id] = true"
            />
            <span
                v-else
                class="text-xl font-extrabold tracking-tight text-gray-400 transition duration-300 group-hover:text-gray-900 sm:text-2xl dark:text-gray-500 dark:group-hover:text-gray-100"
            >
                {{ store.name }}
            </span>
        </Link>
    </div>
</template>

<style scoped>
/* Slide-only entrance so logos are never hidden if the animation is interrupted. */
@keyframes partnerIn {
    from {
        transform: translateY(10px);
    }
    to {
        transform: none;
    }
}

.partner-pop {
    animation: partnerIn 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@media (prefers-reduced-motion: reduce) {
    .partner-pop {
        animation: none;
    }
}
</style>
