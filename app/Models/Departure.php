<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Departure extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'code',
        'program_name',
        'description',
        'facilities',
        'requirements',
        'departure_date',
        'return_date',
        'departure_airport',
        'arrival_airport',
        'airline',
        'flight_number',
        'price',
        'is_public',
        'quota',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'return_date' => 'date',
            'price' => 'integer',
            'is_public' => 'boolean',
            'quota' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function hotels(): BelongsToMany
    {
        return $this->belongsToMany(Hotel::class, 'departure_hotel')
            ->withPivot(['id', 'check_in_at', 'check_out_at', 'sequence'])
            ->withTimestamps();
    }

    public function itineraries(): HasMany
    {
        return $this->hasMany(DepartureItinerary::class)->orderBy('day_number');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(PilgrimRegistration::class);
    }

    public function getRemainingQuotaAttribute(): ?int
    {
        if ($this->quota === null) {
            return null;
        }

        $used = array_key_exists('active_registrations_count', $this->attributes)
            ? (int) $this->attributes['active_registrations_count']
            : $this->registrations()->whereIn('status', ['submitted', 'revision_requested', 'approved', 'in_group'])->count();

        return max(0, $this->quota - $used);
    }

    public function getDurationDaysAttribute(): int
    {
        if (! $this->departure_date || ! $this->return_date) {
            return 0;
        }

        return $this->departure_date->diffInDays($this->return_date) + 1;
    }
}
