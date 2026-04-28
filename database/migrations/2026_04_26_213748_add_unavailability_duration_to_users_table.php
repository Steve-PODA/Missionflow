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
        Schema::table('users', function (Blueprint $table) {
            $table->date('unavailability_start_date')->nullable()->after('unavailability_reason');
            $table->unsignedSmallInteger('unavailability_duration')->nullable()->after('unavailability_start_date');
            $table->string('unavailability_unit', 10)->nullable()->after('unavailability_duration');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['unavailability_start_date', 'unavailability_duration', 'unavailability_unit']);
        });
    }
};
