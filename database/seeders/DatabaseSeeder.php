<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Mission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Créer les rôles et permissions d'abord
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Commandant en chef → rôle admin
        $commandant = User::create([
            'name'               => 'Général PIT👺🔥',
            'email'              => 'test@example.com',
            'password'           => Hash::make('password'),
            'email_verified_at'  => now(),
            'role'               => 'Commandant en chef',
            'avatar'             => 'https://ui-avatars.com/api/?name=General+Moreau&background=1e1f2e&color=fff',
        ]);
        $commandant->assignRole('admin');

        // 3. Personnel de terrain
        $personnel = [
            ['name' => 'Colonel Leclerc',   'email' => 'leclerc@exemple.com',   'role' => 'Officier de renseignement', 'spatie_role' => 'manager'],
            ['name' => 'Capitaine Renard',  'email' => 'renard@exemple.com',    'role' => 'Chef d\'unité',            'spatie_role' => 'manager'],
            ['name' => 'Lieutenant Dubois', 'email' => 'dubois@exemple.com',    'role' => 'Agent de terrain',         'spatie_role' => 'technicien'],
            ['name' => 'Sergent Martin',    'email' => 'martin@exemple.com',    'role' => 'Agent de terrain',         'spatie_role' => 'technicien'],
            ['name' => 'Caporal Petit',     'email' => 'petit@exemple.com',     'role' => 'Agent de terrain',         'spatie_role' => 'technicien'],
        ];

        $agents = collect();
        foreach ($personnel as $p) {
            $user = User::create([
                'name'              => $p['name'],
                'email'             => $p['email'],
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
                'role'              => $p['role'],
                'avatar'            => 'https://ui-avatars.com/api/?name=' . urlencode($p['name']) . '&background=4f6fee&color=fff',
            ]);
            $user->assignRole($p['spatie_role']);
            $agents->push($user);
        }

        $allPersonnel = $agents->concat([$commandant]);

        // Missions de test
        $missions = [
            [
                'title'        => 'Opération Tonnerre',
                'briefing'     => 'Neutralisation d\'une cellule hostile dans le secteur nord. Approche discrète requise. Extraction prévue par la route Delta.',
                'company'      => 'État-Major — Division Alpha',
                'date'         => now()->addDays(2)->format('Y-m-d'),
                'start_time'   => '05:30',
                'duration'     => 4,
                'priority'     => 'urgent',
                'location'     => 'Secteur Nord — Zone 7',
                'client_name'  => 'Général Moreau',
                'client_phone' => '+33 1 00 00 00 01',
                'client_email' => 'moreau@etat-major.mil',
                'status'       => 'pending',
            ],
            [
                'title'        => 'Opération Fantôme',
                'briefing'     => 'Collecte de renseignements sur les mouvements ennemis. Infiltration en zone urbaine. Aucun contact direct autorisé.',
                'company'      => 'DGSE — Bureau des opérations',
                'date'         => now()->format('Y-m-d'),
                'start_time'   => '22:00',
                'duration'     => 8,
                'priority'     => 'high',
                'location'     => 'Zone Urbaine — Secteur Bravo',
                'client_name'  => 'Colonel Leclerc',
                'client_phone' => '+33 1 00 00 00 02',
                'client_email' => 'leclerc@dgse.mil',
                'status'       => 'in_progress',
            ],
            [
                'title'        => 'Opération Bouclier',
                'briefing'     => 'Escorte et protection d\'un convoi stratégique entre la base principale et le poste avancé Charlie.',
                'company'      => 'Commandement Logistique',
                'date'         => now()->subDays(1)->format('Y-m-d'),
                'start_time'   => '08:00',
                'duration'     => 2,
                'priority'     => 'medium',
                'location'     => 'Route Nationale — Axe Charlie',
                'client_name'  => 'Capitaine Renard',
                'client_phone' => '+33 1 00 00 00 03',
                'client_email' => 'renard@logistique.mil',
                'status'       => 'completed',
            ],
        ];

        foreach ($missions as $data) {
            $mission = Mission::create($data);
            $mission->users()->attach(
                $allPersonnel->random(rand(1, 3))->pluck('id')
            );
        }
    }
}
