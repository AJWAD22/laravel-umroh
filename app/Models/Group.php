<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'departure_id',
        'tour_leader_id',
        'muthawwif_id',
        'code',
        'name',
        'capacity',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }

    public function tourLeader(): BelongsTo
    {
        return $this->belongsTo(TourLeader::class);
    }

    public function muthawwif(): BelongsTo
    {
        return $this->belongsTo(Muthawwif::class);
    }

    public function pilgrims(): BelongsToMany
    {
        return $this->belongsToMany(Pilgrim::class, 'group_members')
            ->withPivot(['id', 'joined_at', 'left_at', 'status'])
            ->withTimestamps();
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function pilgrimLocations(): HasMany
    {
        return $this->hasMany(PilgrimLocation::class);
    }

    public function locationHistories(): HasMany
    {
        return $this->hasMany(LocationHistory::class);
    }

    public function sosReports(): HasMany
    {
        return $this->hasMany(SosReport::class);
    }
}
