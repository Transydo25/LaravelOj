<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevisionArticle extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'description', 'content', 'version'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function revisionArticleDetail()
    {
        return $this->hasMany(RevisionArticleDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
