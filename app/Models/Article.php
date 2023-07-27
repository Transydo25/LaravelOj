<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Article extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = ['title', 'description', 'content', 'thumbnail', 'status', 'slug', 'user_id', 'published_at', 'created_at', 'updated_at', 'deleted_at'];

    public function category()
    {
        return $this->belongsToMany(Category::class);
    }
    public function articleDetail()
    {
        return $this->hasMany(ArticleDetail::class);
    }
}
