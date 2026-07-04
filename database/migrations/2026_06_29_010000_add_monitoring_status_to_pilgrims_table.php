<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pilgrims', function (Blueprint $table) {
            $table->enum('monitoring_status', ['normal', 'sos'])
                ->default('normal')
                ->after('status')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('pilgrims', function (Blueprint $table) {
            $table->dropIndex(['monitoring_status']);
            $table->dropColumn('monitoring_status');
        });
    }
};
