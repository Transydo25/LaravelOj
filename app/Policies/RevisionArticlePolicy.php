<?php

namespace App\Policies;

use App\Models\RevisionArticle;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RevisionArticlePolicy
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


    public function update(User $user, RevisionArticle $revision_article)
    {
        return $user->hasRole('editor') || $user->id === $revision_article->user_id;
    }
}
