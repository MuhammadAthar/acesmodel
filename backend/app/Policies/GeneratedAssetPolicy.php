<?php

namespace App\Policies;

use App\Models\GeneratedAsset;
use App\Models\User;

class GeneratedAssetPolicy
{
    public function view(User $user, GeneratedAsset $asset): bool   { return $user->id === $asset->user_id; }
    public function delete(User $user, GeneratedAsset $asset): bool { return $user->id === $asset->user_id; }
}
