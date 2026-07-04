<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          sidebarOpen: false,
          sidebarCollapsed: localStorage.sidebarCollapsed === 'true',
          dark: localStorage.theme === 'dark'
      }"
      :class="{ 'dark': dark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'Umrah Monitor') }}</title>
    <link rel="icon" type="image/png" media="(prefers-color-scheme: light)" href="{{ asset('images/mantau-umroh-icon-light.png') }}">
    <link rel="icon" type="image/png" media="(prefers-color-scheme: dark)" href="{{ asset('images/mantau-umroh-icon-dark.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased selection:bg-blue-200 selection:text-blue-950 dark:bg-slate-950 dark:text-slate-100 dark:selection:bg-blue-800">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(37,99,235,0.045),_transparent_28rem)] dark:bg-[radial-gradient(circle_at_top_right,_rgba(37,99,235,0.09),_transparent_30rem)]">
        @include('layouts.partials.sidebar')

        <div class="min-h-screen transition-[padding] duration-300" :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-[17rem]'">
            @include('layouts.partials.navbar')

            <main class="mx-auto w-full max-w-[1600px] px-4 py-5 sm:px-6 sm:py-7 lg:px-8">
                @isset($header)
                    <div class="mb-6">{{ $header }}</div>
                @endisset
                {{ $slot }}
            </main>
        </div>
    </div>
    <x-toast />
    <x-confirm-dialog />
    @livewireScripts
</body>
</html>
