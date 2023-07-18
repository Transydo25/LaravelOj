<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Traits\HasPermission;


class UserPolicy
{
    use HandlesAuthorization, HasPermission;


    public function update(User $user, User $targetUser)
    {
        return $user->hasRole('admin') || $user->id === $targetUser->id;
    }

    public function delete(User $user, User $targetUser)
    {
        return $user->hasRole('admin');
    }
}
