<?php

namespace App\Policies;

use App\Models\Revision;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RevisionPolicy
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


    public function update(User $user, Revision $revision)
    {
        return $user->hasRole('editor') || $user->id === $revision->user_id;
    }


    public function delete(User $user, Revision $revision)
    {
        //
    }
}
