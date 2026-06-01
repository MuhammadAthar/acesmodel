<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiModel extends Model
{
    protected $table = 'ai_models';

    protected $fillable = [
        'user_id', 'brand_id', 'name', 'ethnicity', 'gender', 'age_range',
        'body_type', 'hair', 'skin_tone', 'style_tags', 'preview_image',
        'replicate_model_ref', 'is_default',
    ];

    protected $casts = [
        'style_tags' => 'array',
        'is_default' => 'boolean',
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
}
