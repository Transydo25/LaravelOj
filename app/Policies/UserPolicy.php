<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;


    public function crud(User $user, User $targetUser)
    {
        return $user->role === 'admin' || $user->id === $targetUser->id;
    }
}
