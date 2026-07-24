<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            if (! Schema::hasColumn('departures', 'facilities')) {
                $table->text('facilities')->nullable()->after('description');
            }
            if (! Schema::hasColumn('departures', 'requirements')) {
                $table->text('requirements')->nullable()->after('facilities');
            }
        });
    }

    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('departures', 'requirements') ? 'requirements' : null,
                Schema::hasColumn('departures', 'facilities') ? 'facilities' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
