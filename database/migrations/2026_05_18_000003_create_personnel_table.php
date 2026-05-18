<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('numero_incorporation')->nullable()->unique();
            $table->string('grade')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('avatar')->nullable();
            $table->foreignId('peloton_id')->nullable()->constrained('pelotons')->nullOnDelete();
            $table->foreignId('groupe_id')->nullable()->constrained('groupes')->nullOnDelete();
            $table->foreignId('equipe_id')->nullable()->constrained('equipes')->nullOnDelete();
            $table->enum('availability', ['available', 'on_leave', 'unavailable'])->default('available');
            $table->date('leave_start_date')->nullable();
            $table->unsignedSmallInteger('leave_duration')->nullable();
            $table->enum('leave_unit', ['days', 'months'])->nullable();
            $table->string('unavailability_reason')->nullable();
            $table->date('unavailability_start_date')->nullable();
            $table->unsignedSmallInteger('unavailability_duration')->nullable();
            $table->enum('unavailability_unit', ['days', 'months'])->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};
