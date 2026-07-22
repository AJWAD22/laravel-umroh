<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->text('description')->nullable()->after('program_name');
            $table->string('airline', 120)->nullable()->after('arrival_airport');
            $table->string('flight_number', 80)->nullable()->after('airline');
            $table->unsignedBigInteger('price')->nullable()->after('flight_number');
            $table->boolean('is_public')->default(true)->after('price')->index();
        });
    }

    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'airline',
                'flight_number',
                'price',
                'is_public',
            ]);
        });
    }
};
