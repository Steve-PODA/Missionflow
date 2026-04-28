<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@exemple.com',
            'password' => Hash::make('password'),
            'role'     => 'Administrateur',
            'avatar'   => null,
        ]);
        $admin->assignRole('admin');

        $personnel = [
            ['name' => 'Colonel Leclerc',   'email' => 'leclerc@exemple.com',  'role' => 'Officier de renseignement', 'spatie' => 'manager'],
            ['name' => 'Capitaine Renard',  'email' => 'renard@exemple.com',   'role' => 'Chef d\'unité',             'spatie' => 'manager'],
            ['name' => 'Lieutenant Dubois', 'email' => 'dubois@exemple.com',   'role' => 'Agent de terrain',          'spatie' => 'agent'],
            ['name' => 'Sergent Martin',    'email' => 'martin@exemple.com',   'role' => 'Agent de terrain',          'spatie' => 'agent'],
            ['name' => 'Caporal Petit',     'email' => 'petit@exemple.com',    'role' => 'Agent de terrain',          'spatie' => 'agent'],
        ];

        foreach ($personnel as $p) {
            $user = User::create([
                'name'     => $p['name'],
                'email'    => $p['email'],
                'password' => Hash::make('password'),
                'role'     => $p['role'],
                'avatar'   => null,
            ]);
            $user->assignRole($p['spatie']);
        }
    }
}
