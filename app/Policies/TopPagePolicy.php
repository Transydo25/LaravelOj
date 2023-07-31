<?php

namespace App\Policies;

use App\Models\TopPage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TopPagePolicy
{
    use HandlesAuthorization;

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


    public function update(User $user, TopPage $top_page)
    {
        return $user->hasRole('editor') || $user->id === $top_page->user_id;
    }
}
