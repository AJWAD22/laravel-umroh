<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Jamaah') · {{ config('app.name', 'Mantau Umroh') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/mantau-umroh-icon-light.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <header class="sticky top-0 z-40 border-b border-white/10 bg-[#061321] text-white shadow-lg shadow-slate-950/10">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 lg:px-8">
            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-3"><img src="{{ asset('images/mantau-umroh-icon-dark.png') }}" alt="Logo" class="size-10 rounded-xl"><div><p class="font-extrabold leading-tight">Portal Jamaah</p><p class="text-[11px] text-slate-400">{{ config('app.name', 'Mantau Umroh') }}</p></div></a>
            <div class="hidden items-center gap-2 md:flex">
                <a href="{{ route('portal.dashboard') }}" class="rounded-xl px-4 py-2 text-sm font-bold {{ request()->routeIs('portal.dashboard') ? 'bg-white/10 text-teal-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">Beranda</a>
                <a href="{{ route('portal.packages.index') }}" class="rounded-xl px-4 py-2 text-sm font-bold {{ request()->routeIs('portal.packages.*') ? 'bg-white/10 text-teal-300' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">Paket Umroh</a>
            </div>
            <div class="flex items-center gap-3"><div class="hidden text-right sm:block"><p class="max-w-40 truncate text-sm font-bold">{{ auth()->user()->name }}</p><p class="text-[11px] text-slate-400">{{ auth()->user()->portalAccount?->phone }}</p></div><form method="POST" action="{{ route('portal.logout') }}">@csrf<button class="rounded-xl border border-white/15 px-3 py-2 text-xs font-bold text-slate-200 hover:bg-white/10">Keluar</button></form></div>
        </nav>
        <div class="mx-auto flex max-w-7xl gap-2 overflow-x-auto px-5 pb-3 md:hidden"><a href="{{ route('portal.dashboard') }}" class="whitespace-nowrap rounded-full bg-white/10 px-4 py-2 text-xs font-bold">Beranda</a><a href="{{ route('portal.packages.index') }}" class="whitespace-nowrap rounded-full bg-white/10 px-4 py-2 text-xs font-bold">Paket Umroh</a></div>
    </header>

    <main class="mx-auto max-w-7xl px-5 py-8 lg:px-8 lg:py-10">
        @if (session('success'))<div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-800">{{ session('error') }}</div>@endif
        @yield('content')
    </main>
</body>
</html>
