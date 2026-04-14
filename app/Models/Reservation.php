<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    ];

    protected function casts(): array
    {
        return [
            'guest_info' => 'array',
            'expires_at' => 'datetime',
            'reserved_by_user_id' => 'integer',
            'user_id' => 'integer',
            'event_id' => 'integer',
            'node_id' => 'integer',
        ];
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
