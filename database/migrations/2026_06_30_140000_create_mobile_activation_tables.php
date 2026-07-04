<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_activation_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('pilgrim_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->char('activation_token_hash', 64)->unique();
            $table->char('numeric_code_hash', 64)->index();
            $table->char('claim_secret_hash', 64)->nullable()->unique();
            $table->string('device_uuid', 120)->nullable();
            $table->string('device_name')->nullable();
            $table->string('platform', 30)->nullable();
            $table->string('status', 30)->default('created')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['pilgrim_id', 'status']);
        });

        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_uuid', 120)->unique();
            $table->string('device_name')->nullable();
            $table->string('platform', 30)->default('android');
            $table->text('fcm_token')->nullable();
            $table->timestamp('activated_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_devices');
        Schema::dropIfExists('mobile_activation_sessions');
    }
};
