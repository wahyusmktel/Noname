<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">

        @php
            $seoBimbel = $page['props']['bimbel'] ?? null;
            $siteName = $seoBimbel['name'] ?? config('app.name', 'Bimbel No name');
            $siteTagline = $seoBimbel['tagline'] ?? 'Bimbingan Belajar Modern Berbasis Prestasi & Terpantau Real-Time';
            $siteDesc = 'Bimbel No name adalah lembaga bimbingan belajar modern untuk jenjang SD, SMP, SMA, dan persiapan SNBT / UTBK dengan tentor profesional, sistem absensi digital QR, dan pemantauan kehadiran serta capaian belajar real-time.';
            $canonicalUrl = url()->current();
            $ogImage = asset('images/og-banner.png');
            $logoImage = asset('images/logo_bnn.png');
            $pageTitle = $siteName . ' - ' . $siteTagline;

            $sameAs = array_values(array_filter([
                $seoBimbel['instagram_url'] ?? null,
                $seoBimbel['tiktok_url'] ?? null,
                $seoBimbel['youtube_url'] ?? null,
                $seoBimbel['facebook_url'] ?? null,
            ]));

            $jsonLd = [
                "@context" => "https://schema.org",
                "@graph" => [
                    [
                        "@type" => "EducationalOrganization",
                        "@id" => url('/') . "/#organization",
                        "name" => $siteName,
                        "url" => url('/'),
                        "logo" => [
                            "@type" => "ImageObject",
                            "url" => $logoImage,
                            "caption" => $siteName,
                        ],
                        "image" => $ogImage,
                        "description" => $siteDesc,
                        "telephone" => $seoBimbel['phone'] ?? '+6281234567890',
                        "email" => $seoBimbel['email'] ?? 'info@bimbelnoname.com',
                        "address" => [
                            "@type" => "PostalAddress",
                            "streetAddress" => $seoBimbel['address'] ?? 'Jl. Pendidikan Utama No. 88',
                            "addressLocality" => $seoBimbel['city'] ?? 'Jakarta Selatan',
                            "addressCountry" => "ID",
                        ],
                        "sameAs" => $sameAs,
                    ],
                    [
                        "@type" => "WebSite",
                        "@id" => url('/') . "/#website",
                        "url" => url('/'),
                        "name" => $siteName,
                        "description" => $siteTagline,
                        "publisher" => [
                            "@id" => url('/') . "/#organization",
                        ],
                        "inLanguage" => "id-ID",
                    ],
                ],
            ];
        @endphp

        <!-- Primary Meta Tags -->
        <meta name="title" content="{{ $pageTitle }}">
        <meta name="description" content="{{ $siteDesc }}">
        <meta name="keywords" content="bimbel, bimbingan belajar, bimbel no name, bimbel sd smp sma, les privat, persiapan snbt, utbk sbmptn, kedinasan, kursus pelajaran, absensi digital bimbel, tentor bimbel berkualitas, bimbel jakarta">
        <meta name="author" content="{{ $siteName }}">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
        <link rel="canonical" href="{{ $canonicalUrl }}">

        <!-- Open Graph / Facebook / WhatsApp / LinkedIn -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $siteDesc }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:secure_url" content="{{ $ogImage }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $siteName }} Banner">
        <meta property="og:locale" content="id_ID">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="{{ $canonicalUrl }}">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $siteDesc }}">
        <meta name="twitter:image" content="{{ $ogImage }}">
        <meta name="twitter:image:alt" content="{{ $siteName }} Banner">

        <!-- Geo & Local SEO -->
        <meta name="geo.region" content="ID">
        <meta name="geo.placename" content="{{ $seoBimbel['city'] ?? 'Jakarta Selatan' }}">

        <!-- Favicons & Mobile Manifest -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#f97316">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ $siteName }}">

        <!-- Structured Data JSON-LD (Schema.org) -->
        <script type="application/ld+json">
        {!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
        </script>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $pageTitle }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
