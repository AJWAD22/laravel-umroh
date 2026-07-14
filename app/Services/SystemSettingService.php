<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemSettingService
{
    private const CACHE_KEY = 'system_settings.all';

    /** Mengelompokkan pengaturan untuk tampilan menu Pengaturan Sistem. */
    public function grouped(): Collection
    {
        return $this->all()->groupBy('group');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->all()->firstWhere('key', $key);

        return $setting ? $this->cast($setting->value, $setting->type) : $default;
    }

    public function update(array $values): void
    {
        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                SystemSetting::query()->where('key', $key)->update(['value' => $value]);
            }
        });

        Cache::forget(self::CACHE_KEY);
    }

    private function all(): Collection
    {
        $settings = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => SystemSetting::query()->orderBy('group')->orderBy('id')->get(),
        );

        // Pengaman untuk server produksi:
        // Jika cache lama/rusak berisi __PHP_Incomplete_Class, dashboard bisa error 500.
        // Saat itu terjadi, cache pengaturan dihapus lalu data pengaturan diambil ulang dari database.
        if (! $settings instanceof Collection) {
            Cache::forget(self::CACHE_KEY);

            return SystemSetting::query()->orderBy('group')->orderBy('id')->get();
        }

        return $settings;
    }

    private function cast(?string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            default => $value,
        };
    }
}
