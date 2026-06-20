// One-off codemod: append `dark:` Tailwind variants to the public storefront.
// Boundary-safe token replacement so e.g. `bg-white/90` and `hover:bg-gray-50`
// are not corrupted. Run once on files that have no dark: variants yet.
import { readFileSync, writeFileSync } from 'node:fs';

const MAP = {
    'text-gray-900': 'dark:text-gray-100',
    'text-gray-800': 'dark:text-gray-100',
    'text-gray-700': 'dark:text-gray-200',
    'text-gray-600': 'dark:text-gray-300',
    'text-gray-500': 'dark:text-gray-400',
    'text-gray-400': 'dark:text-gray-500',
    'text-gray-300': 'dark:text-gray-600',
    'bg-white': 'dark:bg-gray-900',
    'bg-gray-50': 'dark:bg-gray-900',
    'bg-gray-100': 'dark:bg-gray-800',
    'border-gray-100': 'dark:border-gray-800',
    'border-gray-200': 'dark:border-gray-800',
    'border-gray-300': 'dark:border-gray-700',
    'text-blue-600': 'dark:text-blue-400',
    'text-blue-700': 'dark:text-blue-300',
    'text-blue-500': 'dark:text-blue-400',
    'bg-blue-50': 'dark:bg-blue-950/40',
    'bg-blue-100': 'dark:bg-blue-900/40',
    'ring-blue-100': 'dark:ring-blue-900',
    'text-emerald-600': 'dark:text-emerald-400',
    'text-emerald-700': 'dark:text-emerald-400',
    'text-emerald-500': 'dark:text-emerald-400',
    'bg-emerald-50': 'dark:bg-emerald-950/40',
    'text-amber-700': 'dark:text-amber-300',
    'bg-amber-50': 'dark:bg-amber-950/40',
    'hover:bg-gray-50': 'dark:hover:bg-gray-800',
    'hover:bg-gray-100': 'dark:hover:bg-gray-800',
    'from-blue-50': 'dark:from-blue-950/30',
    'to-blue-50': 'dark:to-blue-950/30',
    'to-blue-100': 'dark:to-blue-950/40',
    'from-white': 'dark:from-gray-950',
    'to-white': 'dark:to-gray-950',
};

const esc = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const files = process.argv.slice(2);
for (const file of files) {
    let src = readFileSync(file, 'utf8');
    for (const [light, dark] of Object.entries(MAP)) {
        // token must be bounded by a class-list separator (space or quote) on both
        // sides so we never match a sub-token (e.g. inside `hover:` or `/90`).
        const re = new RegExp(`(?<=[\\s"'])${esc(light)}(?=[\\s"'])`, 'g');
        src = src.replace(re, `${light} ${dark}`);
    }
    writeFileSync(file, src);
    console.log('updated', file);
}
