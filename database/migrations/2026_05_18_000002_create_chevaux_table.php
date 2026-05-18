<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chevaux', function (Blueprint $table) {
            $table->id();
            $table->string('numero_incorporation')->unique();
            $table->foreignId('cavalier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('statut', ['actif', 'malade', 'autre'])->default('actif');
            $table->enum('disponibilite', ['disponible', 'indisponible'])->default('disponible');
            $table->string('indisponibilite_motif')->nullable();
            $table->date('indisponibilite_debut')->nullable();
            $table->unsignedSmallInteger('indisponibilite_duree')->nullable();
            $table->enum('indisponibilite_unite', ['days', 'months'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chevaux');
    }
};
