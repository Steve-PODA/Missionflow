<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->foreignId('equipe_source_id')->nullable()->constrained('equipes')->onDelete('set null');
        });

        Schema::table('mission_user', function (Blueprint $table) {
            $table->string('role_dans_mission')->default('membre');
        });
    }

    public function down(): void
    {
        Schema::table('mission_user', function (Blueprint $table) {
            $table->dropColumn('role_dans_mission');
        });

        Schema::table('missions', function (Blueprint $table) {
            $table->dropForeign(['equipe_source_id']);
            $table->dropColumn('equipe_source_id');
        });
    }
};
