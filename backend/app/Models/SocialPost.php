<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    protected $fillable = [
        'user_id', 'campaign_id', 'platform', 'post_type',
        'caption', 'hashtags', 'media_asset_ids', 'status', 'scheduled_at',
    ];

    protected $casts = [
        'hashtags'        => 'array',
        'media_asset_ids' => 'array',
        'scheduled_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
