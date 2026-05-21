<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permissions = [
            'manage-users',
            'manage-events',
            'manage-theaters',
            'sell-tickets',
            'validate-tickets'
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Crear roles y asignar permisos
        $roleSuperAdmin = Role::findOrCreate('super-admin');
        // El super-admin no necesita tener permisos asignados explícitamente si usamos 
        // un Gate::before, pero aquí le asignamos todos por convención.
        $roleSuperAdmin->givePermissionTo(Permission::all());

        $roleTaquillero = Role::findOrCreate('taquillero');
        $roleTaquillero->givePermissionTo('sell-tickets');

        $roleValidador = Role::findOrCreate('validador');
        $roleValidador->givePermissionTo('validate-tickets');

        // Crear usuario super-admin
        $user = User::updateOrCreate(
            ['email' => 'raul.andres.devs@gmail.com'],
            [
                'name' => 'Raúl Andrés',
                'password' => Hash::make('superadmin123!'),
            ]
        );
        $user->assignRole('super-admin');
    }
}
