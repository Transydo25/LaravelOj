<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;



class PostMeta extends Model
{
    use HasFactory;
    protected $fillable = ['key', 'value'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
