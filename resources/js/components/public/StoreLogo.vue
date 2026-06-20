<script setup lang="ts">
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const props = withDefaults(
    defineProps<{
        name: string;
        logo?: string | null;
        logoDark?: string | null;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    { size: 'md' },
);

const { resolvedAppearance } = useAppearance();

const initials = computed(() => props.name.trim().charAt(0).toUpperCase());

// Use the dark-theme logo only when one was supplied; otherwise fall back to the
// regular logo on a light chip so a dark logo never disappears on a dark page.
const usingDarkLogo = computed(
    () => resolvedAppearance.value === 'dark' && !!props.logoDark,
);

const src = computed(() =>
    usingDarkLogo.value ? props.logoDark : (props.logo ?? null),
);

const darkTile = computed(
    () =>
        usingDarkLogo.value ||
        (!props.logo && !props.logoDark && resolvedAppearance.value === 'dark'),
);

const sizeClasses = computed(
    () =>
        ({
            sm: 'h-10 w-10 text-sm',
            md: 'h-14 w-14 text-lg',
            lg: 'h-20 w-20 text-2xl',
        })[props.size],
);
</script>

<template>
    <div
        :class="[
            sizeClasses,
            'flex shrink-0 items-center justify-center overflow-hidden rounded-xl border',
            darkTile
                ? 'border-gray-700 bg-gray-800'
                : 'border-gray-100 bg-white',
        ]"
    >
        <img
            v-if="src"
            :src="src"
            :alt="name"
            class="h-full w-full object-contain p-1.5"
            loading="lazy"
        />
        <span
            v-else
            class="font-bold"
            :class="darkTile ? 'text-gray-200' : 'text-gray-700'"
            >{{ initials }}</span
        >
    </div>
</template>
