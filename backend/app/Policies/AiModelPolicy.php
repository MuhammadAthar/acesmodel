<?php

namespace App\Policies;

use App\Models\AiModel;
use App\Models\User;

class AiModelPolicy
{
    public function view(User $user, AiModel $aiModel): bool   { return $user->id === $aiModel->user_id; }
    public function update(User $user, AiModel $aiModel): bool { return $user->id === $aiModel->user_id; }
    public function delete(User $user, AiModel $aiModel): bool { return $user->id === $aiModel->user_id; }
}
