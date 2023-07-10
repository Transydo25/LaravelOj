<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'content', 'status', 'slug', 'type'];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
    public function postMeta()
    {
        return $this->hasMany(PostMeta::class);
    }
}
