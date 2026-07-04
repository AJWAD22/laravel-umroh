<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Pilgrim extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'user_id',
        'registration_number',
        'full_name',
        'nik',
        'passport_number',
        'passport_expired_at',
        'gender',
        'phone',
        'birth_date',
        'address',
        'photo_path',
        'status',
        'monitoring_status',
    ];

    protected function casts(): array
    {
        return [
            'passport_expired_at' => 'date',
            'birth_date' => 'date',
            'activation_pin_generated_at' => 'datetime',
            'activation_pin_used_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_members')
            ->withPivot(['id', 'joined_at', 'left_at', 'status'])
            ->withTimestamps();
    }

    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function latestLocation(): HasOne
    {
        return $this->hasOne(PilgrimLocation::class);
    }

    public function locationHistories(): HasMany
    {
        return $this->hasMany(LocationHistory::class);
    }

    public function sosReports(): HasMany
    {
        return $this->hasMany(SosReport::class);
    }

    public function activationSessions(): HasMany
    {
        return $this->hasMany(MobileActivationSession::class);
    }

    public function activationPinCreator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activation_pin_created_by');
    }

    public function activationPin(): ?string
    {
        if (! $this->activation_pin_encrypted || $this->activation_pin_used_at) {
            return null;
        }

        return Crypt::decryptString($this->activation_pin_encrypted);
    }
}
