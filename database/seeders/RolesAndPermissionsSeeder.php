<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Réinitialise le cache Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Permissions ---
        $permissions = [
            'view missions',
            'create missions',
            'edit missions',
            'update mission status',
            'view personnel',
            'manage personnel',
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // --- Rôles et attribution des permissions ---

        // Admin : accès total
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Manager : gestion des missions et du personnel
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view missions',
            'create missions',
            'edit missions',
            'update mission status',
            'view personnel',
            'manage personnel',
        ]);

        // Agent : consultation et mise à jour de statut uniquement
        $technicien = Role::create(['name' => 'agent']);
        $technicien->givePermissionTo([
            'view missions',
            'update mission status',
            'view personnel',
        ]);
    }
}
