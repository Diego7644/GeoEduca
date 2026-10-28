<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('dni', 8);
            $table->string('role', 16);
            $table->string('name', 120);
            $table->string('role_label', 80)->nullable();
            $table->string('password');
            $table->timestamps();

            $table->unique(['dni', 'role']);
            $table->index('dni');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
