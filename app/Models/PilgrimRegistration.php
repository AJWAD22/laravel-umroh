<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilgrimRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'departure_id',
        'full_name',
        'nik',
        'passport_number',
        'gender',
        'phone',
        'birth_date',
        'address',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
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
}
