<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('peloton_id')->nullable()->constrained('pelotons')->onDelete('set null');
            $table->foreignId('groupe_id')->nullable()->constrained('groupes')->onDelete('set null');
            $table->foreignId('equipe_id')->nullable()->constrained('equipes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['equipe_id']);
            $table->dropForeign(['groupe_id']);
            $table->dropForeign(['peloton_id']);
            $table->dropColumn(['peloton_id', 'groupe_id', 'equipe_id']);
        });
    }
};
