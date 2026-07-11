<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pilgrim_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pilgrim_id')->unique()->constrained('pilgrims')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->decimal('heading', 6, 2)->nullable();
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->enum('gps_status', ['online', 'offline', 'unknown'])->default('unknown');
            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            $table->index(['group_id', 'gps_status']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('location_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pilgrim_id')->constrained('pilgrims')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->decimal('heading', 6, 2)->nullable();
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['pilgrim_id', 'recorded_at']);
            $table->index(['group_id', 'recorded_at']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('location_histories');
        Schema::dropIfExists('pilgrim_locations');
    }
};
