<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['name' => 'admin'],
            ['name' => 'user'],
        ];
        DB::table('roles')->insert($roles);

        $permissions = [
            ['name' => 'view'],
            ['name' => 'update'],
            ['name' => 'delete'],
        ];
        DB::table('permissions')->insert($permissions);

        $rolePermissions = [
            ['role_id' => 1, 'permission_id' => 1],
            ['role_id' => 1, 'permission_id' => 2],
            ['role_id' => 1, 'permission_id' => 3],
            ['role_id' => 2, 'permission_id' => 1],
            ['role_id' => 2, 'permission_id' => 2],
            ['role_id' => 2, 'permission_id' => 3],
        ];
        DB::table('role_permission')->insert($rolePermissions);
    }
}
