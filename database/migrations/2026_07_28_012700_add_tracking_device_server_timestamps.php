<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pilgrim_locations', 'location_histories', 'staff_locations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'device_recorded_at')) {
                    $table->timestamp('device_recorded_at')->nullable()->after('recorded_at');
                }

                if (! Schema::hasColumn($tableName, 'server_received_at')) {
                    $table->timestamp('server_received_at')->nullable()->after('device_recorded_at');
                }
            });

            DB::table($tableName)
                ->whereNull('device_recorded_at')
                ->update(['device_recorded_at' => DB::raw('recorded_at')]);

            DB::table($tableName)
                ->whereNull('server_received_at')
                ->update(['server_received_at' => DB::raw('created_at')]);
        }
    }

    public function down(): void
    {
        foreach (['pilgrim_locations', 'location_histories', 'staff_locations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                foreach (['device_recorded_at', 'server_received_at'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
