<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'branch_id',
        'name',
        'email',
        'phone_number',
        'photo_path',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function pilgrim(): HasOne
    {
        return $this->hasOne(Pilgrim::class);
    }

    public function tourLeader(): HasOne
    {
        return $this->hasOne(TourLeader::class);
    }

    public function muthawwif(): HasOne
    {
        return $this->hasOne(Muthawwif::class);
    }

    public function staffLocation(): HasOne
    {
        return $this->hasOne(StaffLocation::class);
    }

    public function handledSosReports(): HasMany
    {
        return $this->hasMany(SosReport::class, 'handled_by');
    }

    public function mobileDevices(): HasMany
    {
        return $this->hasMany(MobileDevice::class);
    }

    public function scopeOfBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->hasValidAdminAccountState()
            && $this->hasAnyRole(...UserRole::webAdminRoles());
    }

    public function hasValidAdminAccountState(): bool
    {
        return $this->is_active
            && (! $this->hasRole(UserRole::BranchAdmin->value) || $this->branch_id !== null);
    }
}
