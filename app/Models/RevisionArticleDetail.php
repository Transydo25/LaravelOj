<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevisionArticleDetail extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'description', 'content',];

    public function revisionArticle()
    {
        return $this->belongsTo(RevisionArticle::class);
    }
}
