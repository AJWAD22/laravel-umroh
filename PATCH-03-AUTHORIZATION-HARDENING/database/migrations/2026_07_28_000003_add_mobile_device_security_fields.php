<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('mobile_devices') && !Schema::hasColumn('mobile_devices', 'revoked_reason')) {
            Schema::table('mobile_devices', function (Blueprint $table) {
                $table->string('revoked_reason')->nullable()->after('revoked_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mobile_devices') && Schema::hasColumn('mobile_devices', 'revoked_reason')) {
            Schema::table('mobile_devices', function (Blueprint $table) {
                $table->dropColumn('revoked_reason');
            });
        }
    }
};
