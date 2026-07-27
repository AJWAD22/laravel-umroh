<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_reports', function (Blueprint $table): void {
            if (! Schema::hasColumn('sos_reports', 'request_id')) {
                $table->string('request_id', 80)->nullable()->after('group_id');
                $table->unique('request_id');
            }

            if (! Schema::hasColumn('sos_reports', 'location_status')) {
                $table->string('location_status', 20)->default('available')->after('accuracy');
            }

            if (! Schema::hasColumn('sos_reports', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('acknowledged_at');
            }

            if (! Schema::hasColumn('sos_reports', 'arrived_at')) {
                $table->timestamp('arrived_at')->nullable()->after('assigned_at');
            }

            if (! Schema::hasColumn('sos_reports', 'resolution_note')) {
                $table->text('resolution_note')->nullable()->after('resolution_notes');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sos_reports MODIFY latitude DECIMAL(10,7) NULL");
            DB::statement("ALTER TABLE sos_reports MODIFY longitude DECIMAL(10,7) NULL");
            DB::statement("ALTER TABLE sos_reports MODIFY status VARCHAR(32) NOT NULL DEFAULT 'new'");

            return;
        }

        Schema::table('sos_reports', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable()->change();
            $table->decimal('longitude', 10, 7)->nullable()->change();
            $table->string('status', 32)->default('new')->change();
        });
    }

    public function down(): void
    {
        Schema::table('sos_reports', function (Blueprint $table): void {
            if (Schema::hasColumn('sos_reports', 'request_id')) {
                $table->dropUnique(['request_id']);
            }

            foreach (['request_id', 'location_status', 'assigned_at', 'arrived_at', 'resolution_note'] as $column) {
                if (Schema::hasColumn('sos_reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
