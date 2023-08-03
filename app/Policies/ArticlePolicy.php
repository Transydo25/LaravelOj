<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Traits\HasPermission;


class ArticlePolicy
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


    public function update(User $user, Article $article)
    {
        return $user->hasRole('editor') || $user->id === $article->user_id;
    }


    public function delete(User $user, Article $article)
    {
        //
    }

    public function restore(User $user, Article $article)
    {
        //
    }
}
