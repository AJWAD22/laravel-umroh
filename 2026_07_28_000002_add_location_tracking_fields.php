<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('location_histories', 'server_received_at')) {
                $table->timestamp('server_received_at')->nullable()->after('recorded_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('location_histories', function (Blueprint $table) {
            if (Schema::hasColumn('location_histories', 'server_received_at')) {
                $table->dropColumn('server_received_at');
            }
        });
    }
};
