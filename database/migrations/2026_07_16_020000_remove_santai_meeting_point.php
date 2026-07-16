<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('checkpoints')
            ->where('category', 'titik_kumpul')
            ->whereRaw('LOWER(name) = ?', ['santai'])
            ->update([
                'is_active' => false,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('checkpoints')
            ->where('category', 'titik_kumpul')
            ->whereRaw('LOWER(name) = ?', ['santai'])
            ->update([
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
    }
};
