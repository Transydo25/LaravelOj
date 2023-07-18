<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use App\Models\Role;


trait HasPermission
{
    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
