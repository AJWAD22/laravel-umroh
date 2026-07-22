<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartureItinerary extends Model
{
    use HasFactory;

    protected $fillable = [
        'departure_id',
        'day_number',
        'title',
        'city',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
        ];
    }

    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }
}
