<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->string('school', 120);
            $table->string('campus', 120);
            $table->string('address', 180);
            $table->string('year', 4);
            $table->string('entry_limit', 5);
            $table->string('exit_time', 5);
            $table->unsignedSmallInteger('geofence_m');
            $table->unsignedSmallInteger('promotion_min');
            $table->unsignedSmallInteger('tolerance_min');
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
