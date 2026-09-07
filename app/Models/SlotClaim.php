<?php

namespace App\Models;

use Database\Factories\SlotClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['barber_id', 'reservation_id', 'slot_start', 'expires_at'])]
class SlotClaim extends Model
{
    /** @use HasFactory<SlotClaimFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'slot_start' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
