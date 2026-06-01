<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedAsset extends Model
{
    protected $fillable = [
        'user_id', 'campaign_id', 'garment_id', 'ai_model_id',
        'asset_type', 'asset_subtype', 'platform',
        'file_path', 'content', 'mime_type', 'file_size', 'width', 'height',
        'ai_provider', 'ai_model_name', 'generation_params',
        'status', 'error_message', 'replicate_prediction_id',
    ];

    protected $casts = [
        'generation_params' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function garment(): BelongsTo
    {
        return $this->belongsTo(Garment::class);
    }

    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }
}
