<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('homeroom_staff_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->string('dni', 8)->unique();
            $table->string('code', 24)->unique();
            $table->string('name', 120);
            $table->string('short_name', 80);
            $table->string('grade', 40);
            $table->string('grade_long', 80);
            $table->string('grade_short', 24);
            $table->string('shift', 40)->default('Turno Mañana');
            $table->string('early_exit_time', 5)->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
