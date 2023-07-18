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
        $rolePermissions = $this->roles()->pluck('id');
        $userPermissions = $this->permissions()->pluck('id');

        $commonPermissions = Permission::whereIn('id', $rolePermissions)
            ->whereIn('id', $userPermissions)
            ->pluck('name');

        return $commonPermissions->contains($permission);
    }



    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
