<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function view(User $user, User $other)
    {
        return $user->club_id === $other->club_id || $user->friends->contains($other);
    }
}
