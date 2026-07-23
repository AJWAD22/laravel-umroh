<?php

use App\Models\Pilgrim;
use App\Models\PilgrimRegistration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80)->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'action']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::table('pilgrims', function (Blueprint $table) {
            if (! Schema::hasColumn('pilgrims', 'nik_hash')) {
                $table->char('nik_hash', 64)->nullable()->after('nik');
            }
            if (! Schema::hasColumn('pilgrims', 'passport_number_hash')) {
                $table->char('passport_number_hash', 64)->nullable()->after('passport_number');
            }
        });

        Schema::table('pilgrim_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('pilgrim_registrations', 'nik_hash')) {
                $table->char('nik_hash', 64)->nullable()->after('nik');
            }
            if (! Schema::hasColumn('pilgrim_registrations', 'passport_number_hash')) {
                $table->char('passport_number_hash', 64)->nullable()->after('passport_number');
            }
        });

        Schema::table('pilgrims', function (Blueprint $table) {
            $table->dropUnique('pilgrims_nik_unique');
            $table->dropUnique('pilgrims_passport_number_unique');
            $table->text('nik')->nullable()->change();
            $table->text('passport_number')->nullable()->change();
            $table->unique('nik_hash');
            $table->unique('passport_number_hash');
        });

        Schema::table('pilgrim_registrations', function (Blueprint $table) {
            $table->text('nik')->nullable()->change();
            $table->text('passport_number')->nullable()->change();
            $table->index('nik_hash');
            $table->index('passport_number_hash');
        });

        $this->securePilgrimIdentities();
        $this->secureRegistrationIdentities();
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        Schema::table('pilgrim_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('pilgrim_registrations', 'nik_hash')) {
                $table->dropIndex(['nik_hash']);
                $table->dropColumn('nik_hash');
            }
            if (Schema::hasColumn('pilgrim_registrations', 'passport_number_hash')) {
                $table->dropIndex(['passport_number_hash']);
                $table->dropColumn('passport_number_hash');
            }
        });

        Schema::table('pilgrims', function (Blueprint $table) {
            if (Schema::hasColumn('pilgrims', 'nik_hash')) {
                $table->dropUnique(['nik_hash']);
                $table->dropColumn('nik_hash');
            }
            if (Schema::hasColumn('pilgrims', 'passport_number_hash')) {
                $table->dropUnique(['passport_number_hash']);
                $table->dropColumn('passport_number_hash');
            }
        });
    }

    private function securePilgrimIdentities(): void
    {
        Pilgrim::query()
            ->select(['id', 'nik', 'passport_number'])
            ->chunkById(200, function ($pilgrims): void {
                foreach ($pilgrims as $pilgrim) {
                    $nik = $this->decryptIfNeeded($pilgrim->getRawOriginal('nik'));
                    $passport = $this->decryptIfNeeded($pilgrim->getRawOriginal('passport_number'));

                    DB::table('pilgrims')
                        ->whereKey($pilgrim->id)
                        ->update([
                            'nik' => filled($nik) ? Crypt::encryptString($nik) : null,
                            'nik_hash' => filled($nik) ? $this->digest($nik) : null,
                            'passport_number' => filled($passport) ? Crypt::encryptString($passport) : null,
                            'passport_number_hash' => filled($passport) ? $this->digest($passport) : null,
                        ]);
                }
            });
    }

    private function secureRegistrationIdentities(): void
    {
        PilgrimRegistration::query()
            ->select(['id', 'nik', 'passport_number'])
            ->chunkById(200, function ($registrations): void {
                foreach ($registrations as $registration) {
                    $nik = $this->decryptIfNeeded($registration->getRawOriginal('nik'));
                    $passport = $this->decryptIfNeeded($registration->getRawOriginal('passport_number'));

                    DB::table('pilgrim_registrations')
                        ->whereKey($registration->id)
                        ->update([
                            'nik' => filled($nik) ? Crypt::encryptString($nik) : null,
                            'nik_hash' => filled($nik) ? $this->digest($nik) : null,
                            'passport_number' => filled($passport) ? Crypt::encryptString($passport) : null,
                            'passport_number_hash' => filled($passport) ? $this->digest($passport) : null,
                        ]);
                }
            });
    }

    private function decryptIfNeeded(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }
    }

    private function digest(string $value): string
    {
        return hash_hmac('sha256', trim($value), (string) config('app.key'));
    }
};
