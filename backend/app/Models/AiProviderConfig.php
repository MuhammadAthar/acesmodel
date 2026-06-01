<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProviderConfig extends Model
{
    protected $table = 'ai_provider_configs';

    protected $fillable = [
        'task_type',
        'tier',
        'provider',
        'model_id',
        'model_name',
        'description',
        'cost_per_use',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'cost_per_use' => 'float',
        'settings'     => 'array',
    ];
}
