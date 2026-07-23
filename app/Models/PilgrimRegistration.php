<?php

namespace App\Models;

use App\Models\Concerns\ProtectsSensitiveIdentity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilgrimRegistration extends Model
{
    use HasFactory, ProtectsSensitiveIdentity;

    protected $fillable = [
        'branch_id',
        'departure_id',
        'user_id',
        'full_name',
        'nik',
        'nik_hash',
        'passport_number',
        'passport_number_hash',
        'passport_expired_at',
        'gender',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'birth_date',
        'address',
        'notes',
        'status',
        'payment_status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'passport_expired_at' => 'date',
            'submitted_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
