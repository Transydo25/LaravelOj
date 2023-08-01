<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Traits\HasPermission;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization, HasPermission;

    public function before(User $user)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
    }

    public function view(User $user, User $targetUser)
    {
        if ($user->hasRole('user')) {
            return $user->id === $targetUser->id
                ? Response::allow()
                : Response::deny('You do not own this post.');
        }
    }

    public function create(User $user)
    {
    }

    public function update(User $user, User $targetUser)
    {
        if ($user->hasRole('user')) {
            return $user->id === $targetUser->id;
        }
    }

    public function delete(User $user)
    {
    }

    public function restore(User $user)
    {
    }

    public function status(User $user)
    {
    }
}
