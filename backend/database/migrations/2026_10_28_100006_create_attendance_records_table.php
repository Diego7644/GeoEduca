<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('attended_on');
            $table->string('status', 24);
            $table->string('method', 24)->nullable();
            $table->string('gate', 40)->nullable();
            $table->time('entry_time')->nullable();
            $table->time('exit_time')->nullable();
            $table->unsignedSmallInteger('late_minutes')->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'attended_on']);
            $table->index('attended_on');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
