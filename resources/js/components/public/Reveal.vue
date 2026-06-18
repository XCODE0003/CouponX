<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from 'vue';

const props = withDefaults(defineProps<{ delay?: number }>(), { delay: 0 });

const el = ref<HTMLElement | null>(null);
const shown = ref(false);
let observer: IntersectionObserver | null = null;

onMounted(() => {
    if (!el.value || typeof IntersectionObserver === 'undefined') {
        shown.value = true;

        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    shown.value = true;
                    observer?.disconnect();
                }
            }
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
    );

    observer.observe(el.value);
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <div
        ref="el"
        class="reveal"
        :class="{ 'reveal--in': shown }"
        :style="{ transitionDelay: `${props.delay}ms` }"
    >
        <slot />
    </div>
</template>

<style scoped>
.reveal {
    opacity: 0;
    transform: translateY(18px);
    transition:
        opacity 0.6s ease,
        transform 0.6s cubic-bezier(0.22, 1, 0.36, 1);
    will-change: opacity, transform;
}

.reveal--in {
    opacity: 1;
    transform: none;
}

@media (prefers-reduced-motion: reduce) {
    .reveal {
        opacity: 1;
        transform: none;
        transition: none;
    }
}
</style>
