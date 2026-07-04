<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pilgrims', function (Blueprint $table) {
            $table->char('activation_pin_hash', 64)->nullable()->unique()->after('monitoring_status');
            $table->text('activation_pin_encrypted')->nullable()->after('activation_pin_hash');
            $table->foreignId('activation_pin_created_by')->nullable()
                ->after('activation_pin_encrypted')->constrained('users')->nullOnDelete();
            $table->timestamp('activation_pin_generated_at')->nullable()->after('activation_pin_created_by');
            $table->timestamp('activation_pin_used_at')->nullable()->after('activation_pin_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('pilgrims', function (Blueprint $table) {
            $table->dropForeign(['activation_pin_created_by']);
            $table->dropUnique(['activation_pin_hash']);
            $table->dropColumn([
                'activation_pin_hash',
                'activation_pin_encrypted',
                'activation_pin_created_by',
                'activation_pin_generated_at',
                'activation_pin_used_at',
            ]);
        });
    }
};
