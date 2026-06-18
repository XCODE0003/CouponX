<script setup lang="ts">
import {
    Car,
    Home,
    Laptop,
    Plane,
    ShoppingBag,
    Shirt,
    Sparkles,
    Tag,
} from '@lucide/vue';
import { computed } from 'vue';
import type { Component } from 'vue';

const props = defineProps<{ name?: string | null; class?: string }>();

const map: Record<string, Component> = {
    laptop: Laptop,
    shirt: Shirt,
    home: Home,
    sparkles: Sparkles,
    plane: Plane,
    car: Car,
    'shopping-bag': ShoppingBag,
    tag: Tag,
};

const icon = computed<Component>(() =>
    props.name ? (map[props.name] ?? Tag) : Tag,
);
</script>

<template>
    <component :is="icon" :class="props.class" />
</template>
