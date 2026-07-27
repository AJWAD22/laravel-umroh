<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_reports', function (Blueprint $table) {
            $table->string('request_id', 100)->nullable()->unique()->after('message');
            $table->enum('location_status', ['available','cached','unavailable'])
                ->default('available')
                ->after('request_id');
        });
    }

    public function down(): void
    {
        Schema::table('sos_reports', function (Blueprint $table) {
            $table->dropColumn(['request_id', 'location_status']);
        });
    }
};
