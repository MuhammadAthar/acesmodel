<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelPersonaPose extends Model
{
    protected $fillable = [
        'persona_id',
        'pose_label',
        'file_path',
        'prompt_used',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(ModelPersona::class, 'persona_id');
    }
}
