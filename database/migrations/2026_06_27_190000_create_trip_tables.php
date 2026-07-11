<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('program_name');
            $table->date('departure_date');
            $table->date('return_date');
            $table->string('departure_airport', 100)->nullable();
            $table->string('arrival_airport', 100)->nullable();
            $table->unsignedInteger('quota')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'departed', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'departure_date']);
        });

        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('name');
            $table->enum('city', ['makkah', 'madinah', 'other'])->default('other');
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('geofence_radius_meters')->default(250);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'city']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('departure_hotel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departure_id')->constrained('departures')->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained('hotels')->restrictOnDelete();
            $table->date('check_in_at')->nullable();
            $table->date('check_out_at')->nullable();
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->timestamps();

            $table->unique(['departure_id', 'hotel_id']);
            $table->index(['departure_id', 'sequence']);
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('departure_id')->nullable()->constrained('departures')->nullOnDelete();
            $table->foreignId('tour_leader_id')->nullable()->constrained('tour_leaders')->nullOnDelete();
            $table->foreignId('muthawwif_id')->nullable()->constrained('muthawwifs')->nullOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->unsignedInteger('capacity')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'is_active']);
        });

        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('pilgrim_id')->constrained('pilgrims')->cascadeOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->enum('status', ['active', 'moved', 'removed'])->default('active');
            $table->timestamps();

            $table->unique(['group_id', 'pilgrim_id']);
            $table->index(['pilgrim_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('departure_hotel');
        Schema::dropIfExists('hotels');
        Schema::dropIfExists('departures');
    }
};
