<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#059669">
        <meta name="description" content="{{ config('app.name') }} — an integrated HRIS, Finance, and ERP platform for managing employees, accounting, and inventory in one system.">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ config('app.name') }}">
        <meta property="og:description" content="An integrated HRIS, Finance, and ERP platform — employees, accounting, and inventory working as one system.">
        <meta property="og:image" content="{{ asset('icon-512.png') }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ config('app.name') }}">
        <meta name="twitter:description" content="An integrated HRIS, Finance, and ERP platform — employees, accounting, and inventory working as one system.">
        <meta name="twitter:image" content="{{ asset('icon-512.png') }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
