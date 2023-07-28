<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopPage extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'description', 'content', 'name', 'status', 'slug'];

    public function topPageDetail()
    {
        return $this->hasMany(TopPageDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
