<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($entries as $entry)
    <url>
        <loc>{{ $entry['loc'] }}</loc>
        @foreach ($entry['alternates'] as $code => $href)
            <xhtml:link rel="alternate" hreflang="{{ \App\Support\Locales::hreflang($code) }}" href="{{ $href }}" />
        @endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $entry['alternates'][\App\Support\Locales::DEFAULT] }}" />
    </url>
@endforeach
</urlset>
