<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Mission;
use App\Models\Peloton;
use App\Models\Groupe;
use App\Models\Equipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Créer les rôles et permissions d'abord
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Création de l'arborescence (1 Peloton, 2 Groupes, 3 Équipes par groupe)
        $peloton = Peloton::create(['nom' => 'Peloton Alpha']);
        
        $groupes = [];
        for ($i = 1; $i <= 2; $i++) {
            $groupe = Groupe::create([
                'nom' => 'Groupe ' . $i,
                'peloton_id' => $peloton->id
            ]);
            $groupes[] = $groupe;

            for ($j = 1; $j <= 3; $j++) {
                Equipe::create([
                    'nom' => 'Équipe ' . $i . '.' . $j,
                    'groupe_id' => $groupe->id
                ]);
            }
        }

        $equipes = Equipe::all();

        // 3. Direction (Non affiliée)
        $commandant = User::create([
            'name'               => 'Général PIT',
            'email'              => 'test@example.com',
            'password'           => Hash::make('password'),
            'email_verified_at'  => now(),
            'role'               => 'commandant',
            'availability'       => 'available',
            'avatar'             => 'https://ui-avatars.com/api/?name=General+PIT&background=1e1f2e&color=fff',
        ]);
        $commandant->assignRole('admin');

        $adjoint = User::create([
            'name'               => 'Colonel Leclerc',
            'email'              => 'leclerc@exemple.com',
            'password'           => Hash::make('password'),
            'email_verified_at'  => now(),
            'role'               => 'adjoint',
            'availability'       => 'available',
            'avatar'             => 'https://ui-avatars.com/api/?name=Colonel+Leclerc&background=4f6fee&color=fff',
        ]);
        $adjoint->assignRole('manager');

        // 4. Opérationnels (Affiliés)
        
        // Compel
        $compel = User::create([
            'name' => 'Capitaine Renard',
            'email' => 'renard@exemple.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'compel',
            'peloton_id' => $peloton->id,
            'availability' => 'available',
        ]);
        $compel->assignRole('manager');

        // Chefs de groupe & Chefs d'équipe & Equipiers
        $agents = collect();
        $agents->push($compel);
        
        foreach ($groupes as $groupe) {
            $chefGroupe = User::create([
                'name' => 'Chef de ' . $groupe->nom,
                'email' => 'chefgroupe' . $groupe->id . '@exemple.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'chef_groupe',
                'peloton_id' => $peloton->id,
                'groupe_id' => $groupe->id,
                'availability' => 'available',
            ]);
            $chefGroupe->assignRole('manager');
            $agents->push($chefGroupe);

            foreach ($groupe->equipes as $equipe) {
                $chefEquipe = User::create([
                    'name' => 'Chef de ' . $equipe->nom,
                    'email' => 'chefequipe' . $equipe->id . '@exemple.com',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'role' => 'chef_equipe',
                    'peloton_id' => $peloton->id,
                    'groupe_id' => $groupe->id,
                    'equipe_id' => $equipe->id,
                    'availability' => 'available',
                ]);
                $chefEquipe->assignRole('agent');
                $agents->push($chefEquipe);

                // 2 équipiers par équipe
                for ($k = 1; $k <= 2; $k++) {
                    // Rend un équipier spécifique indisponible
                    $isUnavailable = ($equipe->id == 1 && $k == 1); 
                    
                    $equipier = User::create([
                        'name' => 'Équipier ' . $k . ' ' . $equipe->nom,
                        'email' => 'equipier' . $k . '_eq' . $equipe->id . '@exemple.com',
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                        'role' => 'equipier',
                        'peloton_id' => $peloton->id,
                        'groupe_id' => $groupe->id,
                        'equipe_id' => $equipe->id,
                        'availability' => $isUnavailable ? 'unavailable' : 'available',
                    ]);
                    $equipier->assignRole('agent');
                    $agents->push($equipier);
                }
            }
        }

        // 5. Missions de test
        $equipeSource = $equipes->first(); // Equipe 1.1

        $mission1 = Mission::create([
            'title'        => 'Opération Tonnerre',
            'briefing'     => 'Neutralisation d\'une cellule hostile...',
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
            'equipe_source_id' => $equipeSource->id,
        ]);

        // Attach membres à la mission 1 avec roles spécifiques
        $membresMission = User::where('equipe_id', $equipeSource->id)->where('availability', 'available')->get();
        if ($membresMission->count() > 0) {
            $chefId = $membresMission->first()->id;
            foreach ($membresMission as $membre) {
                $mission1->users()->attach($membre->id, [
                    'role_dans_mission' => ($membre->id == $chefId) ? 'chef_mission' : 'membre'
                ]);
            }
        }
    }
}
