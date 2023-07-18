<?php

namespace App\Traits;

use App\Models\Role;
use App\Models\Permission;


trait HasPermission
{
    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasPermission($permission)
    {
        foreach ($this->roles as $role) {
            if ($role->permissions()->where('name', $permission)->exists()) {
                return true;
            }
        }
        return false;
    }



    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
