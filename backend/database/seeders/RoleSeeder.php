<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Enums\RoleEnum;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role for sanctum guard if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => RoleEnum::ADMIN, 'guard_name' => 'sanctum']);
        $pengelolaRole = Role::firstOrCreate(['name' => RoleEnum::PENGELOLA, 'guard_name' => 'sanctum']);
        $penelitiRole = Role::firstOrCreate(['name' => RoleEnum::PENELITI, 'guard_name' => 'sanctum']);
        $pengunjungRole = Role::firstOrCreate(['name' => RoleEnum::PENGUNJUNG, 'guard_name' => 'sanctum']);
        
        // Create basic permissions for sanctum guard if they don't exist
        $permissions = [
            'Manage Users',
            'Export Data',
        ];

        $createdPermissions = [];
        foreach ($permissions as $permission) {
            $createdPermissions[] = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }
        
        // Assign permissions to admin role
        $adminRole->syncPermissions($createdPermissions);

        // Assign permissions to pengelola role
        $pengelolaRole->syncPermissions($createdPermissions);

        // Assign specific permissions to peneliti role
        $exportPermission = Permission::where('name', 'Export Data')->where('guard_name', 'sanctum')->first();
        $penelitiRole->syncPermissions([$exportPermission]);

        // Harcoded email adityaerlangga2003@gmail.com assign to admin role not other role
        $admin = User::where('email', 'adityaerlangga2003@gmail.com')->first();
        if(!empty($admin)) {
            $admin->assignRole(RoleEnum::ADMIN);
        }
        
        $this->command->info('Roles and permissions seeded successfully for sanctum guard!');
    }
} 