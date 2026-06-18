<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @php
            $seoProps = $page['props']['seo'] ?? null;
            $metaProps = $page['props']['meta'] ?? null;
            $jsonLdProps = $page['props']['jsonLd'] ?? [];
            $pageTitle = $metaProps['title'] ?? config('app.name', 'CouponX Deals');
            $pageDesc = $metaProps['description'] ?? null;
            $isPublic = str_starts_with($page['component'] ?? '', 'public/');
            $ldFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG;
        @endphp

        <title inertia>{{ $pageTitle }}</title>
        @if ($pageDesc)
            <meta name="description" content="{{ $pageDesc }}">
        @endif
        @if (config('services.google.search_console'))
            <meta name="google-site-verification" content="{{ config('services.google.search_console') }}">
        @endif

        @if ($isPublic && $seoProps)
            <link rel="canonical" href="{{ $seoProps['canonical'] }}">
            @foreach ($seoProps['alternates'] as $alt)
                <link rel="alternate" hreflang="{{ $alt['hreflang'] }}" href="{{ $alt['href'] }}">
            @endforeach
            <meta property="og:type" content="website">
            <meta property="og:title" content="{{ $pageTitle }}">
            @if ($pageDesc)<meta property="og:description" content="{{ $pageDesc }}">@endif
            <meta property="og:url" content="{{ $seoProps['canonical'] }}">
            <meta name="twitter:card" content="summary_large_image">
            <script type="application/ld+json">{!! json_encode(\App\Support\Seo::organization(), $ldFlags) !!}</script>
            @foreach ($jsonLdProps as $ld)
                <script type="application/ld+json">{!! json_encode($ld, $ldFlags) !!}</script>
            @endforeach
        @endif

        @if (config('services.google.analytics_id'))
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google.analytics_id') }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{{ config('services.google.analytics_id') }}');
            </script>
        @endif

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head></x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
