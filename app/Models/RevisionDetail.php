<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevisionDetail extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'description', 'content',];

    public function revision()
    {
        return $this->belongsTo(Revision::class);
    }
}
