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
    protected $dataTypes = ['boolean', 'integer', 'double', 'float', 'string', 'NULL'];



    public function getValueAttribute($value)
    {
        $type = $this->type ?: 'null';

        switch ($type) {
            case 'array':
                return json_decode($value, true);
            case 'file':
                return $value;
        }
        if (in_array($type, $this->dataTypes)) {
            settype($value, $type);
        }

        return $value;
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
