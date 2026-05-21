<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Reservation extends Model
{
    protected $fillable = [
        'event_id',
        'node_id',
        'user_id',
        'reserved_by_user_id',
        'guest_info',
        'status',
        'expires_at',
        'ticket_token',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'guest_info'          => 'array',
            'expires_at'          => 'datetime',
            'validated_at'        => 'datetime',
            'reserved_by_user_id' => 'integer',
            'user_id'             => 'integer',
            'event_id'            => 'integer',
            'node_id'             => 'integer',
        ];
    }

    /**
     * Auto-genera un ticket_token UUID al crear la reservación.
     * Si al confirmar aún no tiene token (reservaciones antiguas), también lo genera.
     */
    protected static function booted(): void
    {
        static::creating(function (self $reservation) {
            $reservation->ticket_token ??= (string) Str::uuid();
        });

        static::updating(function (self $reservation) {
            if ($reservation->status === 'confirmed' && empty($reservation->ticket_token)) {
                $reservation->ticket_token = (string) Str::uuid();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by_user_id');
    }

    public function legacyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
