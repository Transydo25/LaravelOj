<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = ['title', 'content', 'status', 'slug', 'type', 'author', 'created_at', 'updated_at', 'deleted_at'];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
    public function postMeta()
    {
        return $this->hasMany(PostMeta::class);
    }

    public function postDetail()
    {
        return $this->hasMany(PostDetail::class);
    }
}
