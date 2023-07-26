<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    use HasFactory;
    protected $fillable = ['url', 'type_id', 'type_type'];
    public function type()
    {
        return $this->morphTo();
    }
}
