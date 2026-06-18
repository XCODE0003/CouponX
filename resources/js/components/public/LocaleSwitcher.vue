<script setup lang="ts">
import { ChevronDown, Globe } from '@lucide/vue';
import { onClickOutside } from '@vueuse/core';
import { computed, ref } from 'vue';
import { useI18n } from '@/composables/useI18n';

const { locale, locales, alternates } = useI18n();

const open = ref(false);
const root = ref<HTMLElement | null>(null);
onClickOutside(root, () => (open.value = false));

const current = computed(() =>
    locales.value.find((l) => l.code === locale.value),
);
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
            @click="open = !open"
        >
            <Globe class="h-4 w-4" />
            <span class="uppercase">{{ current?.code ?? locale }}</span>
            <ChevronDown class="h-3.5 w-3.5 opacity-60" />
        </button>
        <div
            v-if="open"
            class="absolute right-0 z-50 mt-1 w-40 overflow-hidden rounded-lg border border-gray-100 bg-white py-1 shadow-lg"
        >
            <a
                v-for="l in locales"
                :key="l.code"
                :href="alternates[l.code]"
                class="flex items-center justify-between px-3 py-2 text-sm hover:bg-gray-50"
                :class="
                    l.code === locale
                        ? 'font-semibold text-blue-600'
                        : 'text-gray-700'
                "
            >
                {{ l.native }}
                <span class="text-xs text-gray-400 uppercase">{{
                    l.code
                }}</span>
            </a>
        </div>
    </div>
</template>
