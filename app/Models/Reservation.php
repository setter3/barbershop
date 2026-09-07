<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'reference', 'barber_id', 'customer_id', 'starts_at', 'ends_at', 'status', 'payment_status',
    'base_price_amount', 'services_amount', 'total_amount', 'deposit_percentage', 'deposit_amount',
    'currency', 'expires_at', 'notes',
])]
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    protected static function booted(): void
    {
        static::creating(function (self $reservation): void {
            $reservation->reference ??= (string) Str::ulid();
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'status' => ReservationStatus::class,
            'payment_status' => PaymentStatus::class,
            'base_price_amount' => 'integer',
            'services_amount' => 'integer',
            'total_amount' => 'integer',
            'deposit_percentage' => 'integer',
            'deposit_amount' => 'integer',
            'expires_at' => 'immutable_datetime',
        ];
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot(['name_snapshot', 'price_amount', 'duration_minutes'])
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function slotClaims(): HasMany
    {
        return $this->hasMany(SlotClaim::class);
    }
}
