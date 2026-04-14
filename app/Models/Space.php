<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Space extends Model
{
    protected $fillable = ['building_id', 'name', 'viewport'];

    protected function casts(): array
    {
        return [
            'viewport' => 'array',
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    public function layoutObjects(): HasMany
    {
        return $this->hasMany(LayoutObject::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
