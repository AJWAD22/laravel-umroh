<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pilgrims')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE pilgrims MODIFY monitoring_status ENUM('normal','sos') NOT NULL DEFAULT 'normal'");
            }
        }

        if (Schema::hasTable('sos_reports')) {
            return;
        }

        Schema::create('sos_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('pilgrim_id')->constrained('pilgrims')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'handling', 'resolved'])->default('new');
            $table->timestamp('reported_at')->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status', 'reported_at']);
            $table->index(['group_id', 'status']);
            $table->index(['pilgrim_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sos_reports');

        if (Schema::hasTable('pilgrims') && DB::getDriverName() === 'mysql') {
            DB::table('pilgrims')->where('monitoring_status', 'sos')->update(['monitoring_status' => 'normal']);
            DB::statement("ALTER TABLE pilgrims MODIFY monitoring_status ENUM('normal') NOT NULL DEFAULT 'normal'");
        }
    }
};
