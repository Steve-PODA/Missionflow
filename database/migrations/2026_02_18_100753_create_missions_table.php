<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table principale des missions
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            
            // Infos Mission
            $table->string('title');
            $table->string('company');
            $table->date('date');
            $table->time('start_time');
            $table->decimal('duration', 3, 1)->default(2.0); // Pour gérer 1.5h, 2h, etc.
            $table->string('location');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Infos Contact Client
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('client_phone');

            // Suivi interne
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            
            $table->timestamps();
        });

        // Table pivot pour l'équipe (Lien entre missions et utilisateurs)
        Schema::create('mission_user', function (Blueprint $table) {
            $table->id();
            // Supprime la mission si elle est effacée, et lie à la table users
            $table->foreignId('mission_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_user');
        Schema::dropIfExists('missions');
    }
};