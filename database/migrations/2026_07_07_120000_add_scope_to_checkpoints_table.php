<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->foreignId('departure_id')
                ->nullable()
                ->after('branch_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('group_id')
                ->nullable()
                ->after('departure_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index(['branch_id', 'departure_id', 'group_id', 'is_active'], 'checkpoints_scope_index');
        });
    }

    public function down(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->dropIndex('checkpoints_scope_index');
            $table->dropConstrainedForeignId('group_id');
            $table->dropConstrainedForeignId('departure_id');
        });
    }
};
