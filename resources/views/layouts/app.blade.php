<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $metaTitle ?? $page->meta_title ?? $page->title ?? config('app.name', 'HolartCMS') }}</title>

    @if(isset($metaDescription) || isset($page->meta_description))
    <meta name="description" content="{{ $metaDescription ?? $page->meta_description }}">
    @endif

    @if(isset($metaKeywords) || isset($page->meta_keywords))
    <meta name="keywords" content="{{ $metaKeywords ?? $page->meta_keywords }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    @stack('styles')
</head>
<body>
    @php
        $settings = \HolartWeb\HolartCMS\Models\TPanelSettings::first();

        // Determine which header template to use
        $headerTemplate = 'header1'; // default
        if (isset($page) && $page->header_template) {
            $headerTemplate = $page->header_template;
        } elseif ($settings && $settings->header_template) {
            $headerTemplate = $settings->header_template;
        }

        // Determine which footer template to use
        $footerTemplate = 'footer1'; // default
        if (isset($page) && $page->footer_template) {
            $footerTemplate = $page->footer_template;
        } elseif ($settings && $settings->footer_template) {
            $footerTemplate = $settings->footer_template;
        }
    @endphp

    <!-- Header -->
    @include('layouts.headers.' . $headerTemplate)

    <!-- Main Content -->
    <main>
        @if(isset($page))
            <!-- Render page with blocks using page-renderer component -->
            <x-page-renderer :page="$page" />
        @else
            <!-- Custom content -->
            @yield('content')
        @endif
    </main>

    <!-- Footer -->
    @include('layouts.footers.' . $footerTemplate)

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Scripts -->
    @stack('scripts')
</body>
</html>
