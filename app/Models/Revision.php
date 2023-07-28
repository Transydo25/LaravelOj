<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revision extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'description', 'content', 'version'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function revisionDetail()
    {
        return $this->hasMany(RevisionDetail::class);
    }
}
