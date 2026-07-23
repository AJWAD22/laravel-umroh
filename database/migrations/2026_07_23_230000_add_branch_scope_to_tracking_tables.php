<?php

use App\Models\Pilgrim;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pilgrim_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('pilgrim_locations', 'branch_id')) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('pilgrim_id')
                    ->constrained('branches')
                    ->nullOnDelete();
                $table->index(['branch_id', 'group_id', 'gps_status']);
            }
        });

        Schema::table('location_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('location_histories', 'branch_id')) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('pilgrim_id')
                    ->constrained('branches')
                    ->nullOnDelete();
                $table->index(['branch_id', 'group_id', 'recorded_at']);
            }
        });

        Pilgrim::query()
            ->select(['id', 'branch_id'])
            ->chunkById(500, function ($pilgrims): void {
                foreach ($pilgrims as $pilgrim) {
                    $payload = ['branch_id' => $pilgrim->branch_id];

                    DB::table('pilgrim_locations')
                        ->where('pilgrim_id', $pilgrim->id)
                        ->whereNull('branch_id')
                        ->update($payload);

                    DB::table('location_histories')
                        ->where('pilgrim_id', $pilgrim->id)
                        ->whereNull('branch_id')
                        ->update($payload);
                }
            });
    }

    public function down(): void
    {
        Schema::table('location_histories', function (Blueprint $table) {
            if (Schema::hasColumn('location_histories', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });

        Schema::table('pilgrim_locations', function (Blueprint $table) {
            if (Schema::hasColumn('pilgrim_locations', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }
};
