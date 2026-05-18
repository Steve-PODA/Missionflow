<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Créer les enregistrements personnel pour chaque user avec rôle 'agent'
        $agents = DB::table('users')
            ->join('model_has_roles', function ($j) {
                $j->on('users.id', '=', 'model_has_roles.model_id')
                  ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'agent')
            ->select('users.*')
            ->get();

        $userToPersonnel = [];

        foreach ($agents as $agent) {
            $personnelId = DB::table('personnel')->insertGetId([
                'name'                      => $agent->name,
                'numero_incorporation'      => $agent->numero_incorporation ?? null,
                'grade'                     => $agent->role ?? null,
                'phone_number'              => $agent->phone_number ?? null,
                'avatar'                    => $agent->avatar ?? null,
                'peloton_id'                => $agent->peloton_id ?? null,
                'groupe_id'                 => $agent->groupe_id ?? null,
                'equipe_id'                 => $agent->equipe_id ?? null,
                'availability'              => $agent->availability ?? 'available',
                'leave_start_date'          => $agent->leave_start_date ?? null,
                'leave_duration'            => $agent->leave_duration ?? null,
                'leave_unit'                => $agent->leave_unit ?? null,
                'unavailability_reason'     => $agent->unavailability_reason ?? null,
                'unavailability_start_date' => $agent->unavailability_start_date ?? null,
                'unavailability_duration'   => $agent->unavailability_duration ?? null,
                'unavailability_unit'       => $agent->unavailability_unit ?? null,
                'user_id'                   => $agent->id,
                'created_at'                => now(),
                'updated_at'                => now(),
            ]);

            $userToPersonnel[$agent->id] = $personnelId;
        }

        // 2. Migrer mission_user → mission_personnel
        $missionUsers = DB::table('mission_user')->get();

        foreach ($missionUsers as $mu) {
            if (isset($userToPersonnel[$mu->user_id])) {
                DB::table('mission_personnel')->insertOrIgnore([
                    'mission_id'        => $mu->mission_id,
                    'personnel_id'      => $userToPersonnel[$mu->user_id],
                    'role_dans_mission' => $mu->role_dans_mission ?? null,
                    'peloton_name'      => $mu->peloton_name ?? null,
                    'groupe_name'       => $mu->groupe_name ?? null,
                    'equipe_name'       => $mu->equipe_name ?? null,
                    'created_at'        => $mu->created_at,
                    'updated_at'        => $mu->updated_at,
                ]);
            }
        }

        // 3. Migrer chevaux.cavalier_id (user_id → personnel_id)
        // D'abord supprimer la contrainte FK existante
        Schema::table('chevaux', function (Blueprint $table) {
            $table->dropForeign(['cavalier_id']);
        });

        $chevaux = DB::table('chevaux')->whereNotNull('cavalier_id')->get();
        foreach ($chevaux as $cheval) {
            $personnelId = $userToPersonnel[$cheval->cavalier_id] ?? null;
            DB::table('chevaux')->where('id', $cheval->id)->update([
                'cavalier_id' => $personnelId,
            ]);
        }

        // Ajouter la nouvelle contrainte FK vers personnel
        Schema::table('chevaux', function (Blueprint $table) {
            $table->foreign('cavalier_id')->references('id')->on('personnel')->nullOnDelete();
        });

        // 4. Supprimer l'ancienne table mission_user
        Schema::dropIfExists('mission_user');

        // 5. Retirer les champs opérationnels de la table users
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['peloton_id']);
            $table->dropForeign(['groupe_id']);
            $table->dropForeign(['equipe_id']);
            $table->dropColumn([
                'role',
                'availability',
                'leave_start_date',
                'leave_duration',
                'leave_unit',
                'unavailability_reason',
                'unavailability_start_date',
                'unavailability_duration',
                'unavailability_unit',
                'peloton_id',
                'groupe_id',
                'equipe_id',
                'numero_incorporation',
            ]);
        });
    }

    public function down(): void
    {
        // Migration irréversible (données reconstruites depuis les users)
    }
};
