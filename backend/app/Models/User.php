<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'plan',
        'credits',
        'avatar',
        'role',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
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

    public function generatedAssets(): HasMany
    {
        return $this->hasMany(GeneratedAsset::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function deductCredits(int $amount): bool
    {
        if ($this->credits < $amount) {
            return false;
        }
        $this->decrement('credits', $amount);
        CreditTransaction::create([
            'user_id'     => $this->id,
            'amount'      => -$amount,
            'type'        => 'generation',
            'description' => 'Asset generation',
            'reference_type' => 'App\Models\User',
            'reference_id'   => $this->id,
        ]);
        return true;
    }

    /**
     * Deduct 1 credit per successfully generated asset.
     * Called from the generation job AFTER the image is saved.
     * Logs a transaction linked to the specific GeneratedAsset.
     */
    public function deductCreditForAsset(int $amount, \App\Models\GeneratedAsset $asset): bool
    {
        if ($this->credits < $amount) {
            \Illuminate\Support\Facades\Log::warning('Credit balance insufficient for asset deduction — generation already completed', [
                'user_id'  => $this->id,
                'asset_id' => $asset->id,
                'balance'  => $this->credits,
                'needed'   => $amount,
            ]);
            return false;
        }
        $this->decrement('credits', $amount);
        CreditTransaction::create([
            'user_id'        => $this->id,
            'amount'         => -$amount,
            'type'           => 'generation',
            'description'    => 'Generated ' . $asset->asset_subtype . ' ' . $asset->asset_type
                                . ' — Campaign #' . $asset->campaign_id,
            'reference_type' => \App\Models\GeneratedAsset::class,
            'reference_id'   => $asset->id,
        ]);
        return true;
    }
}
