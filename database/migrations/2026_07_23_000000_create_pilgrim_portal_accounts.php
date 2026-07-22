<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pilgrim_portal_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone', 30)->unique();
            $table->string('email')->nullable()->unique();
            $table->timestamps();
        });

        Schema::table('pilgrim_registrations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('emergency_contact_name')->nullable()->after('phone');
            $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name');
            $table->date('passport_expired_at')->nullable()->after('passport_number');
            $table->string('payment_status', 30)->default('pending_branch_payment')->after('status')->index();
            $table->timestamp('submitted_at')->nullable()->after('payment_status');
            $table->unique(['user_id', 'departure_id'], 'pilgrim_registration_user_departure_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pilgrim_registrations', function (Blueprint $table) {
            $table->dropUnique('pilgrim_registration_user_departure_unique');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'emergency_contact_name',
                'emergency_contact_phone',
                'passport_expired_at',
                'payment_status',
                'submitted_at',
            ]);
        });

        Schema::dropIfExists('pilgrim_portal_accounts');
    }
};
