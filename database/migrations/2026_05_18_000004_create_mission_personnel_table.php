<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->string('role_dans_mission')->nullable();
            $table->string('peloton_name')->nullable();
            $table->string('groupe_name')->nullable();
            $table->string('equipe_name')->nullable();
            $table->timestamps();
            $table->unique(['mission_id', 'personnel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_personnel');
    }
};
