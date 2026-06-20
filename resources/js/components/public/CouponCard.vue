<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Check,
    Clock,
    Copy,
    ExternalLink,
    Sparkles,
    Tag,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { useI18n } from '@/composables/useI18n';
import { copyToClipboard } from '@/lib/clipboard';
import type { CouponData } from '@/types/public';
import StoreLogo from './StoreLogo.vue';

const props = defineProps<{ coupon: CouponData; showStore?: boolean }>();

const { t } = useI18n();

const revealed = ref(false);
const copied = ref(false);

const typeLabel = computed(() => {
    return {
        code: t('coupon.code_label'),
        deal: t('coupon.deal_label'),
        sale: t('coupon.sale_label'),
    }[props.coupon.type];
});

const typeClasses = computed(() => {
    return {
        code: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400',
        deal: 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300',
        sale: 'bg-rose-50 text-rose-700',
    }[props.coupon.type];
});

const expiry = computed(() => {
    if (!props.coupon.expires_at) {
        return null;
    }

    const days = Math.ceil(
        (new Date(props.coupon.expires_at).getTime() - Date.now()) / 86_400_000,
    );

    if (days <= 0) {
        return t('coupon.expires_today');
    }

    return t('coupon.expires_in', { days });
});

async function activate(): Promise<void> {
    // Always open the cloaked redirect in a new tab.
    window.open(props.coupon.out_url, '_blank', 'noopener');

    if (props.coupon.has_code && props.coupon.code) {
        revealed.value = true;
        const ok = await copyToClipboard(props.coupon.code);

        if (ok) {
            copied.value = true;
            toast.success(t('coupon.copied'));
            window.setTimeout(() => (copied.value = false), 2500);
        }
    }
}
</script>

<template>
    <article
        class="flex flex-col gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition hover:shadow-md sm:flex-row sm:items-center sm:p-5 dark:border-gray-800 dark:bg-gray-900"
    >
        <!-- Leading icon / store logo -->
        <StoreLogo
            v-if="showStore && coupon.store"
            :name="coupon.store.name"
            :logo="coupon.store.logo"
            :logo-dark="coupon.store.logo_dark"
            size="md"
        />
        <div
            v-else
            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl text-white"
            :class="{
                'bg-emerald-500': coupon.type === 'code',
                'bg-blue-500': coupon.type === 'deal',
                'bg-rose-500': coupon.type === 'sale',
            }"
        >
            <Tag v-if="coupon.type !== 'sale'" class="h-6 w-6" />
            <Sparkles v-else class="h-6 w-6" />
        </div>

        <!-- Main content -->
        <div class="min-w-0 flex-1">
            <div class="mb-1 flex flex-wrap items-center gap-2">
                <span
                    class="rounded-md px-2 py-0.5 text-[11px] font-semibold tracking-wide uppercase"
                    :class="typeClasses"
                >
                    {{ typeLabel }}
                </span>
                <span
                    v-if="coupon.is_exclusive"
                    class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300"
                >
                    <Sparkles class="h-3 w-3" /> {{ t('coupon.exclusive') }}
                </span>
                <Link
                    v-if="showStore && coupon.store"
                    :href="coupon.store.url"
                    class="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400"
                >
                    {{ coupon.store.name }}
                </Link>
            </div>

            <h3
                class="truncate text-base font-semibold text-gray-900 dark:text-gray-100"
            >
                {{ coupon.title }}
            </h3>
            <p
                v-if="coupon.description"
                class="mt-0.5 line-clamp-2 text-sm text-gray-500 dark:text-gray-400"
            >
                {{ coupon.description }}
            </p>

            <div
                class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400 dark:text-gray-500"
            >
                <span
                    v-if="coupon.is_verified"
                    class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400"
                >
                    <BadgeCheck class="h-3.5 w-3.5" />
                    {{ t('coupon.verified') }}
                </span>
                <span v-if="expiry" class="inline-flex items-center gap-1">
                    <Clock class="h-3.5 w-3.5" /> {{ expiry }}
                </span>
                <span>{{
                    t('coupon.used_times', { count: coupon.used_count })
                }}</span>
            </div>
        </div>

        <!-- Action area -->
        <div class="flex shrink-0 flex-col items-stretch gap-2 sm:w-44">
            <button
                v-if="coupon.has_code"
                type="button"
                class="group relative flex items-center justify-between gap-2 overflow-hidden rounded-lg border border-dashed border-blue-300 bg-blue-50/50 px-3 py-2 text-sm font-semibold text-blue-700 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-300"
                @click="activate"
            >
                <span class="truncate" :class="{ 'blur-[3px]': !revealed }">{{
                    coupon.code
                }}</span>
                <Check
                    v-if="copied"
                    class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                />
                <Copy
                    v-else
                    class="h-4 w-4 shrink-0 opacity-60 group-hover:opacity-100"
                />
            </button>
            <button
                type="button"
                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                @click="activate"
            >
                {{
                    coupon.has_code
                        ? t('coupon.show_code')
                        : t('coupon.get_deal')
                }}
                <ExternalLink class="h-4 w-4" />
            </button>
        </div>
    </article>
</template>
