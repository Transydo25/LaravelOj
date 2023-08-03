<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopPageDetail extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'area', 'summary', 'about', 'lang'];

    public function topPage()
    {
        return $this->belongsTo(TopPage::class);
    }
}
