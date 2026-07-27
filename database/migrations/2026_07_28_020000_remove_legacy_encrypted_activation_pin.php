<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pilgrims', 'activation_pin_encrypted')) {
            return;
        }

        Schema::table('pilgrims', function (Blueprint $table) {
            $table->dropColumn('activation_pin_encrypted');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('pilgrims', 'activation_pin_encrypted')) {
            return;
        }

        Schema::table('pilgrims', function (Blueprint $table) {
            $table->text('activation_pin_encrypted')->nullable()->after('activation_pin_hash');
        });
    }
};
