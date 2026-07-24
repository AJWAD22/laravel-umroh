<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pilgrim_registrations MODIFY status VARCHAR(40) NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE pilgrim_registrations MODIFY payment_status VARCHAR(40) NOT NULL DEFAULT 'unpaid'");
            DB::statement('ALTER TABLE pilgrim_registrations MODIFY gender VARCHAR(20) NULL');
        } else {
            Schema::table('pilgrim_registrations', function (Blueprint $table) {
                $table->string('status', 40)->default('draft')->change();
                $table->string('payment_status', 40)->default('unpaid')->change();
                $table->string('gender', 20)->nullable()->change();
            });
        }

        Schema::table('pilgrim_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('pilgrim_registrations', 'health_notes')) {
                $table->text('health_notes')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('pilgrim_registrations', 'document_notes')) {
                $table->text('document_notes')->nullable()->after('health_notes');
            }
            if (! Schema::hasColumn('pilgrim_registrations', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('document_notes');
            }
            if (! Schema::hasColumn('pilgrim_registrations', 'identity_document_path')) {
                $table->string('identity_document_path')->nullable()->after('photo_path');
            }
            if (! Schema::hasColumn('pilgrim_registrations', 'passport_document_path')) {
                $table->string('passport_document_path')->nullable()->after('identity_document_path');
            }
            if (! Schema::hasColumn('pilgrim_registrations', 'revision_notes')) {
                $table->text('revision_notes')->nullable()->after('passport_document_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pilgrim_registrations', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('pilgrim_registrations', 'revision_notes') ? 'revision_notes' : null,
                Schema::hasColumn('pilgrim_registrations', 'passport_document_path') ? 'passport_document_path' : null,
                Schema::hasColumn('pilgrim_registrations', 'identity_document_path') ? 'identity_document_path' : null,
                Schema::hasColumn('pilgrim_registrations', 'photo_path') ? 'photo_path' : null,
                Schema::hasColumn('pilgrim_registrations', 'document_notes') ? 'document_notes' : null,
                Schema::hasColumn('pilgrim_registrations', 'health_notes') ? 'health_notes' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
