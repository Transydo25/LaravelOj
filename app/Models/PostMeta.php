<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostMeta extends Model
{
    use HasFactory;
    protected $fillable = ['path', 'link', 'meta_key'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
