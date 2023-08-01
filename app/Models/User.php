<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Traits\HasPermission;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable, HasPermission;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'deleted_at',
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function userMeta()
    {
        return $this->hasMany(UserMeta::class);
    }

    public function article()
    {
        return $this->hasMany(Article::class);
    }

    public function revisionArticle()
    {
        return $this->hasMany(RevisionArticle::class);
    }

    public function topPage()
    {
        return $this->hasOne(TopPage::class);
    }
}
