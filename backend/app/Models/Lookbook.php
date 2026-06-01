<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lookbook extends Model
{
    protected $fillable = [
        'user_id', 'campaign_id', 'title', 'status', 'pages',
        'pdf_path', 'web_slug', 'is_public',
    ];

    protected $casts = [
        'pages'     => 'array',
        'is_public' => 'boolean',
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
