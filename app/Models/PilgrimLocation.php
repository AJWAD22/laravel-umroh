<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilgrimLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'pilgrim_id',
        'branch_id',
        'group_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'heading',
        'battery_level',
        'gps_status',
        'recorded_at',
        'device_recorded_at',
        'server_received_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'decimal:2',
            'speed' => 'decimal:2',
            'heading' => 'decimal:2',
            'battery_level' => 'integer',
            'recorded_at' => 'datetime',
            'device_recorded_at' => 'datetime',
            'server_received_at' => 'datetime',
        ];
    }

    public function pilgrim(): BelongsTo
    {
        return $this->belongsTo(Pilgrim::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
