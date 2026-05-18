<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            $table->string('fonction')->nullable()->after('grade');
        });

        // Migrer les données existantes : grade → fonction
        \DB::table('personnel')->update(['fonction' => \DB::raw('grade')]);

        Schema::table('personnel', function (Blueprint $table) {
            $table->string('grade')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            $table->dropColumn('fonction');
        });
    }
};
