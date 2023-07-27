<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Traits\HasPermission;


class PostPolicy
{
    use HandlesAuthorization, HasPermission;

    public function before(User $user)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    public function create(User $user)
    {
        return $user->hasRole('editor');
    }

    public function update(User $user, Post $post)
    {
        return $user->hasRole('editor') || $user->id === $post->author;
    }

    public function delete(User $user)
    {
    }

    public function restore(User $user)
    {
    }
}
