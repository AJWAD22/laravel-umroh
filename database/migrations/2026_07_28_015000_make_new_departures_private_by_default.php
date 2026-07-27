<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE departures MODIFY is_public TINYINT(1) NOT NULL DEFAULT 0');

            return;
        }

        Schema::table('departures', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE departures MODIFY is_public TINYINT(1) NOT NULL DEFAULT 1');

            return;
        }

        Schema::table('departures', function (Blueprint $table) {
            $table->boolean('is_public')->default(true)->change();
        });
    }
};
