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

        // Noms pour la génération
        $chefPelotonNames = ['Capitaine Renard', 'Capitaine Dubois'];
        $chefGroupeNames  = ['Lieutenant Martin', 'Lieutenant Leroy', 'Lieutenant Bernard', 'Lieutenant Richard'];
        $chefEquipeNames  = ['Sergent Petit', 'Sergent Durand', 'Sergent Moreau', 'Sergent Simon', 'Sergent Laurent', 'Sergent Michel', 'Sergent Garcia', 'Sergent David'];
        $equipierNames    = [
            'Caporal Roux', 'Soldat Blanc', 'Soldat Lefebvre', 'Caporal Mercier', 
            'Soldat Girard', 'Caporal Blanc', 'Soldat Fournier', 'Soldat Morel',
            'Caporal Robin', 'Soldat Girardot', 'Soldat Clement', 'Soldat Rousseau',
            'Caporal Lemaire', 'Soldat Lucas', 'Soldat Francois', 'Soldat Perrin'
        ];

        $cgIndex = 0;
        $ceIndex = 0;
        $eqIndex = 0;

        // 2. Création de l'arborescence (2 Pelotons, 2 Groupes par peloton, 2 Équipes par groupe)
        $pelotonsData = ['Peloton Alpha', 'Peloton Bravo'];
        $equipesCreated = collect();

        foreach ($pelotonsData as $pIndex => $pName) {
            $peloton = Peloton::create(['nom' => $pName]);

            // Chef de peloton (Compel)
            $compel = User::create([
                'name'              => $chefPelotonNames[$pIndex],
                'email'             => 'compel_' . strtolower(explode(' ', $pName)[1]) . '@exemple.com',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
                'role'              => 'Chef de peloton',
                'peloton_id'        => $peloton->id,
                'availability'      => 'available',
            ]);
            $compel->assignRole('manager');

            for ($i = 1; $i <= 2; $i++) {
                $groupe = Groupe::create([
                    'nom'        => 'Groupe ' . $i . ' (' . explode(' ', $pName)[1] . ')',
                    'peloton_id' => $peloton->id
                ]);

                // Chef de groupe
                $chefGroupe = User::create([
                    'name'              => $chefGroupeNames[$cgIndex++],
                    'email'             => 'chefgroupe_' . $groupe->id . '@exemple.com',
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                    'role'              => 'Chef de groupe',
                    'peloton_id'        => $peloton->id,
                    'groupe_id'         => $groupe->id,
                    'availability'      => 'available',
                ]);
                $chefGroupe->assignRole('manager');

                for ($j = 1; $j <= 2; $j++) {
                    $equipe = Equipe::create([
                        'nom'       => 'Équipe ' . $i . '.' . $j . ' (' . explode(' ', $pName)[1] . ')',
                        'groupe_id' => $groupe->id
                    ]);
                    $equipesCreated->push($equipe);

                    // Chef d'équipe
                    $chefEquipe = User::create([
                        'name'              => $chefEquipeNames[$ceIndex++],
                        'email'             => 'chefequipe_' . $equipe->id . '@exemple.com',
                        'password'          => Hash::make('password'),
                        'email_verified_at' => now(),
                        'role'              => 'Chef d\'équipe',
                        'peloton_id'        => $peloton->id,
                        'groupe_id'         => $groupe->id,
                        'equipe_id'         => $equipe->id,
                        'availability'      => 'available',
                    ]);
                    $chefEquipe->assignRole('agent');

                    // 2 équipiers par équipe
                    for ($k = 1; $k <= 2; $k++) {
                        $isUnavailable = ($equipe->id == 1 && $k == 1); 
                        
                        $equipier = User::create([
                            'name'              => $equipierNames[$eqIndex++],
                            'email'             => 'equipier_' . $equipe->id . '_' . $k . '@exemple.com',
                            'password'          => Hash::make('password'),
                            'email_verified_at' => now(),
                            'role'              => 'Équipier',
                            'peloton_id'        => $peloton->id,
                            'groupe_id'         => $groupe->id,
                            'equipe_id'         => $equipe->id,
                            'availability'      => $isUnavailable ? 'unavailable' : 'available',
                        ]);
                        $equipier->assignRole('agent');
                    }
                }
            }
        }

        // 3. Direction (Non affiliée)
        $commandant = User::create([
            'name'               => 'Général PIT',
            'email'              => 'test@example.com',
            'password'           => Hash::make('password'),
            'email_verified_at'  => now(),
            'role'               => 'Commandant',
            'availability'       => 'available',
            'avatar'             => 'https://ui-avatars.com/api/?name=General+PIT&background=1e1f2e&color=fff',
        ]);
        $commandant->assignRole('admin');

        $adjoint = User::create([
            'name'               => 'Colonel Leclerc',
            'email'              => 'leclerc@exemple.com',
            'password'           => Hash::make('password'),
            'email_verified_at'  => now(),
            'role'               => 'Adjoint',
            'availability'       => 'available',
            'avatar'             => 'https://ui-avatars.com/api/?name=Colonel+Leclerc&background=4f6fee&color=fff',
        ]);
        $adjoint->assignRole('manager');

        // 4. Missions de test
        $equipeSource = $equipesCreated->first();

        $mission1 = Mission::create([
            'title'            => 'Opération Tonnerre',
            'briefing'         => 'Neutralisation d\'une cellule hostile identifiée dans le secteur Nord. Intervention rapide requise avec équipement lourd.',
            'company'          => 'État-Major — Division Alpha',
            'date'             => now()->addDays(2)->format('Y-m-d'),
            'start_time'       => '05:30',
            'duration'         => 4,
            'priority'         => 'urgent',
            'location'         => 'Secteur Nord — Zone 7',
            'client_name'      => 'Général Moreau',
            'client_phone'     => '+33 1 00 00 00 01',
            'client_email'     => 'moreau@etat-major.mil',
            'status'           => 'pending',
            'equipe_source_id' => $equipeSource->id,
        ]);

        // Attach membres à la mission 1 avec les infos historiques d'unités
        $membresMission = User::with(['peloton', 'groupe', 'equipe'])
            ->where('equipe_id', $equipeSource->id)
            ->where('availability', 'available')
            ->get();

        if ($membresMission->count() > 0) {
            $chefId = $membresMission->firstWhere('role', 'Chef d\'équipe')?->id ?? $membresMission->first()->id;
            foreach ($membresMission as $membre) {
                $mission1->users()->attach($membre->id, [
                    'role_dans_mission' => ($membre->id == $chefId) ? 'chef_mission' : 'membre',
                    'peloton_name'      => $membre->peloton?->nom,
                    'groupe_name'       => $membre->groupe?->nom,
                    'equipe_name'       => $membre->equipe?->nom,
                ]);
            }
        }
    }
}
