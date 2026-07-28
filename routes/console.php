<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\MobileActivationService;

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

Artisan::command('activation-pins:rotate {--days=15 : Umur maksimum PIN sebelum dibuat ulang}', function (): int {
    $days = max(1, (int) $this->option('days'));
    $result = app(MobileActivationService::class)->rotateExpiredPins($days);

    $this->info("PIN dirotasi: {$result['rotated']}; dilewati: {$result['skipped']}.");

    return self::SUCCESS;
})->purpose('Merotasi PIN aktivasi lama serta mencabut perangkat aktif');

Schedule::command('activation-pins:rotate --days=15')
    ->dailyAt('01:30')
    ->withoutOverlapping();
