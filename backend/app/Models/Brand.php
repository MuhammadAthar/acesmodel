<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = [
        'user_id', 'name', 'logo', 'website', 'description',
        'colors', 'tone', 'photography_style', 'model_preferences',
        'aesthetic_tags', 'dna_analyzed',
    ];

    protected $casts = [
        'colors'             => 'array',
        'model_preferences'  => 'array',
        'aesthetic_tags'     => 'array',
        'dna_analyzed'       => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function garments(): HasMany
    {
        return $this->hasMany(Garment::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function aiModels(): HasMany
    {
        return $this->hasMany(AiModel::class);
    }
}
