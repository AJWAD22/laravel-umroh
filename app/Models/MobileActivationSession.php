<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileActivationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'pilgrim_id',
        'created_by',
        'approved_by',
        'activation_token_hash',
        'numeric_code_hash',
        'claim_secret_hash',
        'device_uuid',
        'device_name',
        'platform',
        'status',
        'expires_at',
        'claimed_at',
        'approved_at',
        'completed_at',
    ];

    protected $hidden = [
        'activation_token_hash',
        'numeric_code_hash',
        'claim_secret_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'claimed_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function pilgrim(): BelongsTo
    {
        return $this->belongsTo(Pilgrim::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
