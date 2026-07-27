<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('location:cleanup {--days=90 : Jumlah hari histori lokasi yang tetap disimpan}', function (): int {
    $days = max(1, (int) $this->option('days'));
    $cutoff = now()->subDays($days);

    $deleted = DB::table('location_histories')
        ->where('recorded_at', '<', $cutoff)
        ->delete();

    $this->info("Histori lokasi sebelum {$cutoff->toDateTimeString()} dihapus: {$deleted}");

    return self::SUCCESS;
})->purpose('Menghapus histori lokasi lama tanpa menghapus lokasi terakhir Live Map');

Schedule::command('location:cleanup --days=90')->dailyAt('02:00');
