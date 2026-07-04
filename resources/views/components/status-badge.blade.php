@props(['value', 'label' => null])

@php
    $normalized = strtolower((string) $value);
    $styles = match ($normalized) {
        'active', 'online', 'completed', 'resolved', 'yes', '1', 'true' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300',
        'sos', 'cancelled', 'offline', 'inactive', 'no', '0', 'false' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/50 dark:text-red-300',
        'scheduled', 'acknowledged', 'departed', 'registered' => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/50 dark:text-blue-300',
        'draft', 'pending', 'unknown' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/50 dark:text-amber-300',
        default => 'bg-slate-100 text-slate-700 ring-slate-500/20 dark:bg-slate-800 dark:text-slate-300',
    };

    $translated = match ($normalized) {
        'active' => 'Aktif',
        'inactive' => 'Tidak Aktif',
        'online' => 'Online',
        'offline' => 'Offline',
        'completed', 'resolved' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'scheduled' => 'Terjadwal',
        'departed' => 'Berangkat',
        'registered' => 'Terdaftar',
        'acknowledged' => 'Ditangani',
        'draft' => 'Draft',
        'pending' => 'Menunggu',
        'unknown' => 'Tidak Diketahui',
        'sos' => 'SOS',
        'yes', '1', 'true' => 'Ya',
        'no', '0', 'false' => 'Tidak',
        default => str($normalized)->headline(),
    };
@endphp

<span {{ $attributes->class("inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$styles}") }}>
    {{ $label ?? $translated }}
</span>
