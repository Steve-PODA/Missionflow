<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Général Moreau',
            'email'    => 'admin@exemple.com',
            'password' => Hash::make('password'),
            'role'     => 'Commandant en chef',
            'avatar'   => 'https://ui-avatars.com/api/?name=General+Moreau&background=1e1f2e&color=fff',
        ]);

        $personnel = [
            ['name' => 'Colonel Leclerc',    'email' => 'leclerc@exemple.com',    'role' => 'Officier de renseignement'],
            ['name' => 'Capitaine Renard',   'email' => 'renard@exemple.com',     'role' => 'Chef d\'unité'],
            ['name' => 'Lieutenant Dubois',  'email' => 'dubois@exemple.com',     'role' => 'Agent de terrain'],
            ['name' => 'Sergent Martin',     'email' => 'martin@exemple.com',     'role' => 'Agent de terrain'],
            ['name' => 'Caporal Petit',      'email' => 'petit@exemple.com',      'role' => 'Agent de terrain'],
        ];

        foreach ($personnel as $p) {
            User::create([
                'name'     => $p['name'],
                'email'    => $p['email'],
                'password' => Hash::make('password'),
                'role'     => $p['role'],
                'avatar'   => 'https://ui-avatars.com/api/?name=' . urlencode($p['name']) . '&background=4f6fee&color=fff',
            ]);
        }
    }
}
