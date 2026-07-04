<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Umrah Monitor') }}</title>
        <link rel="icon" type="image/png" media="(prefers-color-scheme: light)" href="{{ asset('images/mantau-umroh-icon-light.png') }}">
        <link rel="icon" type="image/png" media="(prefers-color-scheme: dark)" href="{{ asset('images/mantau-umroh-icon-dark.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-950 px-4 py-10">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(37,99,235,0.3),_transparent_38%),radial-gradient(circle_at_bottom_left,_rgba(14,165,233,0.18),_transparent_35%)]"></div>
            <div class="relative w-full sm:max-w-md">
                <div class="mb-6 text-center text-white">
                    <img src="{{ asset('images/mantau-umroh-icon-dark.png') }}" alt="Logo Mantau Umroh" class="mx-auto size-24 rounded-3xl object-contain shadow-2xl shadow-blue-950/60">
                    <h1 class="mt-4 text-2xl font-bold">Umrah Monitoring</h1>
                    <p class="mt-1 text-sm text-slate-300">GIS & GPS Control Center</p>
                </div>
                <div class="overflow-hidden rounded-3xl border border-white/10 bg-white p-7 shadow-2xl">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
