<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Deal $deal): bool
    {
        return $this->owns($user, $deal);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Deal $deal): bool
    {
        return $this->owns($user, $deal);
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $this->owns($user, $deal);
    }

    private function owns(User $user, Deal $deal): bool
    {
        return $user->isAdmin() || $deal->contact->user_id === $user->id;
    }
}
