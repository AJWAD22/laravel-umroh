<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departure_itineraries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departure_id')->constrained('departures')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->string('title');
            $table->string('city', 80)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['departure_id', 'day_number']);
            $table->index(['departure_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departure_itineraries');
    }
};
