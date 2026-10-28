<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('justifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('guardian_user_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 32);
            $table->date('event_date');
            $table->string('event_time', 5)->nullable();
            $table->string('reason', 1000);
            $table->string('attachment_name', 120)->nullable();
            $table->string('status', 16)->default('submitted');
            $table->timestamps();

            $table->index(['student_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justifications');
    }
};
