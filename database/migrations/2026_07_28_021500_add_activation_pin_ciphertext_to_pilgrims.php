<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pilgrims', function (Blueprint $table): void {
            $table->text('activation_pin_ciphertext')
                ->nullable()
                ->after('activation_pin_hash');
        });
    }

    public function down(): void
    {
        Schema::table('pilgrims', function (Blueprint $table): void {
            $table->dropColumn('activation_pin_ciphertext');
        });
    }
};
