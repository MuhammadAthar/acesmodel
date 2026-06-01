<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelPersona extends Model
{
    protected $fillable = [
        'name', 'gender', 'age', 'nationality', 'ethnicity',
        'skin_tone', 'body_type', 'hair', 'best_for',
        'description', 'avatar_url', 'is_active', 'sort_order',
        'character_seed', 'character_lock_prompt',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'age'             => 'integer',
        'sort_order'      => 'integer',
        'character_seed'  => 'integer',
    ];

    public function poses()
    {
        return $this->hasMany(ModelPersonaPose::class, 'persona_id')
                    ->orderBy('sort_order')
                    ->orderBy('id');
    }

    /**
     * Build a rich AI prompt description from the persona's metadata.
     * Used as the model description in image generation prompts.
     * Admin can override entirely with a custom `description` field.
     */
    public function toPromptDescription(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $genderLabel = match ($this->gender) {
            'male'       => 'male',
            'female'     => 'female',
            'boy'        => 'young boy',
            'girl'       => 'young girl',
            'child'      => 'child',
            'non_binary' => 'androgynous',
            default      => $this->gender,
        };

        $ageDesc = match (true) {
            $this->age === null                        => '',
            $this->age <= 4                            => 'toddler, ',
            $this->age <= 12                           => 'child age ' . $this->age . ', ',
            $this->age <= 17                           => 'teenager age ' . $this->age . ', ',
            $this->age <= 25                           => 'young adult age ' . $this->age . ', ',
            $this->age <= 40                           => 'adult age ' . $this->age . ', ',
            default                                    => 'mature adult age ' . $this->age . ', ',
        };

        $parts = array_filter([
            "a professional {$genderLabel} fashion model",
            $this->age ? $ageDesc . 'years old' : null,
            $this->nationality ? "{$this->nationality} nationality" : null,
            $this->ethnicity ? "{$this->ethnicity} ethnicity" : null,
            $this->skin_tone ? "{$this->skin_tone} skin tone" : null,
            $this->body_type ? str_replace('_', ' ', $this->body_type) . ' build' : null,
            $this->hair ?: null,
            'perfect posture, elegant and confident',
        ]);

        return implode(', ', $parts);
    }

    /** Infer whether this persona is a minor (for safe content handling). */
    public function isMinor(): bool
    {
        if ($this->gender === 'boy' || $this->gender === 'girl' || $this->gender === 'child') {
            return true;
        }
        return $this->age !== null && $this->age < 18;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
