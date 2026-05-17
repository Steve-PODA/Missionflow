<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mission_user', function (Blueprint $table) {
            $table->string('peloton_name')->nullable();
            $table->string('groupe_name')->nullable();
            $table->string('equipe_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mission_user', function (Blueprint $table) {
            $table->dropColumn(['peloton_name', 'groupe_name', 'equipe_name']);
        });
    }
};
