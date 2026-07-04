<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pilgrims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('registration_number', 40)->unique();
            $table->string('full_name');
            $table->string('nik', 20)->nullable()->unique();
            $table->string('passport_number', 30)->nullable()->unique();
            $table->date('passport_expired_at')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->string('phone', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('photo_path')->nullable();
            $table->enum('status', ['registered', 'active', 'completed', 'cancelled'])->default('registered');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'full_name']);
        });

        Schema::create('tour_leaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('employee_number', 40)->unique();
            $table->string('full_name');
            $table->string('phone', 30)->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'is_active']);
            $table->index(['branch_id', 'full_name']);
        });

        Schema::create('muthawwifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('employee_number', 40)->unique();
            $table->string('full_name');
            $table->string('phone', 30)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('languages')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'is_active']);
            $table->index(['branch_id', 'full_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muthawwifs');
        Schema::dropIfExists('tour_leaders');
        Schema::dropIfExists('pilgrims');
    }
};
