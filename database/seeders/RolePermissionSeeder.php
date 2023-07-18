<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['name' => 'customer'],
            ['name' => 'editor'],
        ];
        DB::table('roles')->insert($roles);

        $permissions = [
            ['name' => 'create_post'],
            ['name' => 'update_post'],
            ['name' => 'delete_post'],
        ];
        DB::table('permissions')->insert($permissions);

        $rolePermissions = [
            ['role_id' => 5, 'permission_id' => 7],
            ['role_id' => 5, 'permission_id' => 8],
            ['role_id' => 6, 'permission_id' => 7],
            ['role_id' => 6, 'permission_id' => 8],
            ['role_id' => 6, 'permission_id' => 9],
        ];
        DB::table('role_permission')->insert($rolePermissions);
    }
}
