<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { AtSign, Menu, MessageCircle, Rss, Send, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import LocaleSwitcher from '@/components/public/LocaleSwitcher.vue';
import SearchBar from '@/components/public/SearchBar.vue';
import { useI18n } from '@/composables/useI18n';
import type { CategoryData } from '@/types/public';

const { t, locale } = useI18n();
const page = usePage();

const mobileOpen = ref(false);

const navCategories = computed(() =>
    (
        (page.props.nav as { categories?: CategoryData[] } | undefined)
            ?.categories ?? []
    ).slice(0, 5),
);

const navLinks = computed(() => [
    { label: t('nav.stores'), href: `/${locale.value}/stores` },
    { label: t('nav.categories'), href: `/${locale.value}/categories` },
    { label: t('nav.blog'), href: `/${locale.value}/blog` },
]);

const companyLinks = computed(() => [
    { label: t('footer.about_us'), href: `/${locale.value}/about` },
    { label: t('footer.contact'), href: `/${locale.value}/contact` },
    { label: t('footer.privacy'), href: `/${locale.value}/privacy` },
    { label: t('footer.terms'), href: `/${locale.value}/terms` },
]);

const helpLinks = computed(() => [
    { label: t('footer.how_it_works'), href: `/${locale.value}/how-it-works` },
    { label: t('footer.faq'), href: `/${locale.value}/faq` },
    { label: t('footer.search'), href: `/${locale.value}/search` },
    { label: t('footer.sitemap'), href: `/${locale.value}/sitemap` },
]);

// Re-key the page wrapper per path so the enter animation replays on navigation.
const pageKey = computed(() => page.url.split('?')[0]);

const year = new Date().getFullYear();

// Surface flash toasts (e.g. newsletter subscription).
watch(
    () =>
        (
            page.props.flash as
                | { toast?: { type: 'success' | 'error'; message: string } }
                | undefined
        )?.toast,
    (flash) => {
        if (flash?.message) {
            toast[flash.type ?? 'success'](flash.message);
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-gray-900">
        <!-- Header -->
        <header
            class="sticky top-0 z-40 border-b border-gray-100 bg-white/90 backdrop-blur"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8"
            >
                <Link
                    :href="`/${locale}`"
                    class="flex shrink-0 items-center gap-1.5"
                >
                    <img
                        src="/coupon.png"
                        :alt="`${t('brand')} ${t('brand_suffix')}`"
                        class="h-6 w-auto"
                    />
                    <span class="text-lg font-extrabold text-gray-900">{{
                        t('brand')
                    }}</span>
                </Link>

                <nav class="ml-4 hidden items-center gap-6 md:flex">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="text-sm font-medium text-gray-600 transition hover:text-blue-600"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <div class="mx-auto hidden w-full max-w-sm md:block">
                    <SearchBar />
                </div>

                <div class="ml-auto flex items-center gap-1">
                    <LocaleSwitcher />
                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-600 hover:bg-gray-50 md:hidden"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <X v-if="mobileOpen" class="h-5 w-5" />
                        <Menu v-else class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div
                v-if="mobileOpen"
                class="border-t border-gray-100 px-4 py-3 md:hidden"
            >
                <SearchBar class="mb-3" />
                <nav class="flex flex-col gap-1">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="rounded-lg px-2 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        @click="mobileOpen = false"
                    >
                        {{ link.label }}
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1">
            <div :key="pageKey" class="page-transition">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-16 border-t border-gray-100 bg-gray-50">
            <div
                class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8"
            >
                <div>
                    <div class="flex items-center gap-1.5">
                        <img
                            src="/coupon.png"
                            :alt="`${t('brand')} ${t('brand_suffix')}`"
                            class="h-6 w-auto"
                        />
                        <span class="text-lg font-extrabold text-gray-900">{{
                            t('brand')
                        }}</span>
                    </div>
                    <p class="mt-3 max-w-xs text-sm text-gray-500">
                        {{ t('footer.tagline') }}
                    </p>
                    <div class="mt-4 flex items-center gap-3 text-gray-400">
                        <a href="#" aria-label="X" class="hover:text-blue-600"
                            ><AtSign class="h-4 w-4"
                        /></a>
                        <a
                            href="#"
                            aria-label="Telegram"
                            class="hover:text-blue-600"
                            ><Send class="h-4 w-4"
                        /></a>
                        <a
                            href="#"
                            aria-label="Chat"
                            class="hover:text-blue-600"
                            ><MessageCircle class="h-4 w-4"
                        /></a>
                        <a href="#" aria-label="RSS" class="hover:text-blue-600"
                            ><Rss class="h-4 w-4"
                        /></a>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-900">
                        {{ t('footer.company') }}
                    </h3>
                    <ul class="mt-3 space-y-2 text-sm text-gray-500">
                        <li v-for="link in companyLinks" :key="link.href">
                            <Link
                                :href="link.href"
                                class="hover:text-blue-600"
                                >{{ link.label }}</Link
                            >
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-900">
                        {{ t('footer.help') }}
                    </h3>
                    <ul class="mt-3 space-y-2 text-sm text-gray-500">
                        <li v-for="link in helpLinks" :key="link.href">
                            <Link
                                :href="link.href"
                                class="hover:text-blue-600"
                                >{{ link.label }}</Link
                            >
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-900">
                        {{ t('footer.categories') }}
                    </h3>
                    <ul class="mt-3 space-y-2 text-sm text-gray-500">
                        <li v-for="cat in navCategories" :key="cat.id">
                            <Link :href="cat.url" class="hover:text-blue-600">{{
                                cat.name
                            }}</Link>
                        </li>
                    </ul>
                </div>
            </div>
            <div
                class="border-t border-gray-100 py-5 text-center text-xs text-gray-400"
            >
                {{ t('footer.rights', { year }) }}
            </div>
        </footer>
    </div>
</template>
