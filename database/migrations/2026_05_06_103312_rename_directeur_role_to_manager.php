<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $directeurId = DB::table('roles')->where('name', 'directeur')->value('id');
        $managerId   = DB::table('roles')->where('name', 'manager')->value('id');

        if (! $directeurId) {
            return; // rôle déjà absent
        }

        if ($managerId) {
            // Réassigner les utilisateurs qui ont le rôle directeur
            DB::table('model_has_roles')
                ->where('role_id', $directeurId)
                ->whereNotExists(function ($q) use ($managerId) {
                    $q->from('model_has_roles as mhr2')
                        ->whereColumn('mhr2.model_id', 'model_has_roles.model_id')
                        ->whereColumn('mhr2.model_type', 'model_has_roles.model_type')
                        ->where('mhr2.role_id', $managerId);
                })
                ->update(['role_id' => $managerId]);

            // Supprimer les doublons restants (si l'utilisateur avait déjà manager)
            DB::table('model_has_roles')
                ->where('role_id', $directeurId)
                ->delete();
        } else {
            // Pas de rôle manager : renommer directeur en manager
            DB::table('roles')->where('id', $directeurId)->update(['name' => 'manager']);
            return;
        }

        // Supprimer le rôle directeur et ses permissions
        DB::table('role_has_permissions')->where('role_id', $directeurId)->delete();
        DB::table('roles')->where('id', $directeurId)->delete();
    }

    public function down(): void
    {
        // Irréversible : on ne recrée pas directeur
    }
};
