import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type TranslationTree = Record<string, unknown>;

export interface LocaleOption {
    code: string;
    label: string;
    native: string;
}

function lookup(tree: TranslationTree, key: string): string | undefined {
    const value = key.split('.').reduce<unknown>((acc, part) => {
        if (acc && typeof acc === 'object') {
            return (acc as TranslationTree)[part];
        }

        return undefined;
    }, tree);

    return typeof value === 'string' ? value : undefined;
}

export function useI18n() {
    const page = usePage();

    const locale = computed(
        () => (page.props.locale as string | undefined) ?? 'en',
    );
    const locales = computed(
        () => (page.props.locales as LocaleOption[] | undefined) ?? [],
    );
    const alternates = computed(
        () =>
            (page.props.alternates as Record<string, string> | undefined) ?? {},
    );

    function t(
        key: string,
        replacements: Record<string, string | number> = {},
    ): string {
        const tree =
            (page.props.translations as TranslationTree | undefined) ?? {};
        let str = lookup(tree, key) ?? key;

        for (const [token, replacement] of Object.entries(replacements)) {
            str = str.replace(
                new RegExp(`:${token}`, 'g'),
                String(replacement),
            );
        }

        return str;
    }

    return { t, locale, locales, alternates };
}
