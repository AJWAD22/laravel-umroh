<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Masuk · {{ config('app.name', 'Mantau Umroh') }}</title>
        <link rel="icon" type="image/png" media="(prefers-color-scheme: light)" href="{{ asset('images/mantau-umroh-icon-light.png') }}">
        <link rel="icon" type="image/png" media="(prefers-color-scheme: dark)" href="{{ asset('images/mantau-umroh-icon-dark.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <main class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-8">
            <section class="w-full max-w-md">
                <div class="mb-6 text-center">
                    <img src="{{ asset('images/mantau-umroh-icon-light.png') }}" alt="Logo Mantau Umroh"
                         class="mx-auto size-16 rounded-2xl object-contain shadow-sm ring-1 ring-slate-200">
                    <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-950">Mantau Umroh</h1>
                    <p class="mt-1 text-sm text-slate-500">Portal administrasi monitoring jamaah</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    {{ $slot }}
                </div>

                <p class="mt-5 text-center text-xs text-slate-400">
                    © {{ date('Y') }} Mantau Umroh
                </p>
            </section>
        </main>
    </body>
</html>
