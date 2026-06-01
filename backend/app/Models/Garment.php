<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Garment extends Model
{
    protected $fillable = [
        'user_id', 'brand_id', 'name', 'original_image', 'processed_image',
        'category', 'gender', 'season', 'colors', 'fabric', 'texture',
        'style', 'ai_tags', 'analyzed',
    ];

    protected $casts = [
        'colors'   => 'array',
        'ai_tags'  => 'array',
        'analyzed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function generatedAssets(): HasMany
    {
        return $this->hasMany(GeneratedAsset::class);
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_garments');
    }
}
