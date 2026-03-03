<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $metaTitle ?? config('app.name', 'HolartCMS') }}</title>

    @if(isset($metaDescription))
    <meta name="description" content="{{ $metaDescription }}">
    @endif

    @if(isset($metaKeywords))
    <meta name="keywords" content="{{ $metaKeywords }}">
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
        $headerTemplate = $headerTemplate ?? ($settings->header_template ?? 'header1');

        // Determine which footer template to use
        $footerTemplate = $footerTemplate ?? ($settings->footer_template ?? 'footer1');
    @endphp

    <!-- Header -->
    @include('layouts.headers.' . $headerTemplate)

    <!-- Main Content - You write your own HTML here -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    @include('layouts.footers.' . $footerTemplate)

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Scripts -->
    @stack('scripts')
</body>
</html>
