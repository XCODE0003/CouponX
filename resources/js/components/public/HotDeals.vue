<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Bell, Check, Copy, ExternalLink, Flame } from '@lucide/vue';
import { computed, reactive } from 'vue';
import { toast } from 'vue-sonner';
import { useI18n } from '@/composables/useI18n';
import { copyToClipboard } from '@/lib/clipboard';
import type { CouponData } from '@/types/public';
import StoreLogo from './StoreLogo.vue';

const props = withDefaults(
    defineProps<{
        coupons: CouponData[];
        viewAllUrl: string;
        newsletterAnchor?: string;
    }>(),
    { newsletterAnchor: '#newsletter' },
);

const { t } = useI18n();

const items = computed(() => props.coupons.slice(0, 4));
const copied = reactive<Record<number, boolean>>({});
const revealed = reactive<Record<number, boolean>>({});

function typeLabel(coupon: CouponData): string {
    return {
        code: t('coupon.code_label'),
        deal: t('coupon.deal_label'),
        sale: t('coupon.sale_label'),
    }[coupon.type];
}

function typeClasses(coupon: CouponData): string {
    return {
        code: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400',
        deal: 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300',
        sale: 'bg-rose-50 text-rose-700',
    }[coupon.type];
}

async function activate(coupon: CouponData): Promise<void> {
    window.open(coupon.out_url, '_blank', 'noopener');

    if (coupon.has_code && coupon.code) {
        revealed[coupon.id] = true;
        const ok = await copyToClipboard(coupon.code);

        if (ok) {
            copied[coupon.id] = true;
            toast.success(t('coupon.copied'));
            window.setTimeout(() => (copied[coupon.id] = false), 2500);
        }
    }
}
</script>

<template>
    <div class="hotdeals-float relative">
        <div
            class="absolute -inset-6 -z-10 rounded-[3rem] bg-blue-200/30 blur-3xl"
        ></div>

        <div
            class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900"
        >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 pt-6 pb-2">
                <h2
                    class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-gray-100"
                >
                    <Flame class="h-5 w-5 text-orange-500" />
                    {{ t('hot.title') }}
                </h2>
                <Link
                    :href="viewAllUrl"
                    class="text-sm font-semibold text-blue-600 hover:underline dark:text-blue-400"
                >
                    {{ t('hot.view_all') }}
                </Link>
            </div>

            <!-- Deals -->
            <ul class="divide-y divide-gray-100 px-3 dark:divide-gray-800">
                <li
                    v-for="(coupon, i) in items"
                    :key="coupon.id"
                    class="hotdeals-row flex items-center gap-3 rounded-2xl px-3 py-4 transition hover:bg-gray-50/80 sm:gap-4 dark:hover:bg-gray-800/50"
                    :style="{ animationDelay: `${0.15 + i * 0.1}s` }"
                >
                    <StoreLogo
                        v-if="coupon.store"
                        :name="coupon.store.name"
                        :logo="coupon.store.logo"
                        :logo-dark="coupon.store.logo_dark"
                        size="md"
                    />

                    <div class="min-w-0 flex-1">
                        <span
                            class="mb-1 inline-block rounded-md px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase"
                            :class="typeClasses(coupon)"
                        >
                            {{ typeLabel(coupon) }}
                        </span>
                        <p
                            class="truncate font-semibold text-gray-900 dark:text-gray-100"
                        >
                            {{ coupon.title }}
                        </p>
                        <p
                            v-if="coupon.description"
                            class="truncate text-sm text-gray-400 dark:text-gray-500"
                        >
                            {{ coupon.description }}
                        </p>
                    </div>

                    <div
                        class="hidden shrink-0 flex-col items-end gap-1 sm:flex"
                    >
                        <button
                            v-if="coupon.has_code"
                            type="button"
                            class="group flex items-center gap-2 rounded-lg border border-dashed border-blue-300 bg-blue-50/40 px-3 py-1.5 text-sm font-semibold text-blue-700 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-300"
                            @click="activate(coupon)"
                        >
                            <span
                                class="font-mono"
                                :class="{ 'blur-[3px]': !revealed[coupon.id] }"
                                >{{ coupon.code }}</span
                            >
                            <Check
                                v-if="copied[coupon.id]"
                                class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"
                            />
                            <Copy
                                v-else
                                class="h-3.5 w-3.5 opacity-60 group-hover:opacity-100"
                            />
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 hover:underline dark:text-blue-400"
                            @click="activate(coupon)"
                        >
                            {{
                                coupon.has_code
                                    ? t('coupon.show_code')
                                    : t('coupon.get_deal')
                            }}
                            <ExternalLink class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <!-- Compact action on mobile -->
                    <button
                        type="button"
                        class="shrink-0 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white sm:hidden"
                        @click="activate(coupon)"
                    >
                        {{
                            coupon.has_code
                                ? t('coupon.show_code')
                                : t('coupon.get_deal')
                        }}
                    </button>
                </li>
            </ul>

            <!-- Newsletter CTA -->
            <div
                class="m-3 flex items-center gap-4 rounded-2xl bg-blue-50/70 p-4 dark:bg-blue-950/20"
            >
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400"
                >
                    <Bell class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ t('hot.cta_title') }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ t('hot.cta_text') }}
                    </p>
                </div>
                <a
                    :href="newsletterAnchor"
                    class="shrink-0 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                >
                    {{ t('hot.subscribe') }}
                </a>
            </div>
        </div>
    </div>
</template>
