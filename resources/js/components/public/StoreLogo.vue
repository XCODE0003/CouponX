<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        name: string;
        logo?: string | null;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    { size: 'md' },
);

const initials = computed(() => props.name.trim().charAt(0).toUpperCase());

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
            'flex shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-100 bg-white',
        ]"
    >
        <img
            v-if="logo"
            :src="logo"
            :alt="name"
            class="h-full w-full object-contain p-1.5"
            loading="lazy"
        />
        <span v-else class="font-bold text-gray-700">{{ initials }}</span>
    </div>
</template>
