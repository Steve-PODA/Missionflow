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
            $table->date('leave_start_date')->nullable()->after('availability');
            $table->unsignedSmallInteger('leave_duration')->nullable()->after('leave_start_date');
            $table->string('leave_unit', 10)->nullable()->after('leave_duration'); // 'days' ou 'months'
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['leave_start_date', 'leave_duration', 'leave_unit']);
        });
    }
};
