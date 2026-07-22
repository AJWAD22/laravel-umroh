<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pilgrim_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('departure_id')->constrained('departures')->restrictOnDelete();
            $table->string('full_name');
            $table->string('nik', 20)->nullable();
            $table->string('passport_number', 30)->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->string('phone', 30);
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['submitted', 'contacted', 'approved', 'cancelled'])->default('submitted')->index();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['departure_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pilgrim_registrations');
    }
};
