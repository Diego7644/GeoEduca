<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('source', 16)->default('manual')->after('status');
        });

        Schema::table('qr_tokens', function (Blueprint $table) {
            $table->unique('value');
        });

        Schema::create('attendance_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 16);
            $table->string('result', 24);
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->unsignedSmallInteger('accuracy_m')->nullable();
            $table->string('detail', 255)->nullable();
            $table->timestamps();

            $table->index(['kind', 'result']);
            $table->index('created_at');
        });

        Schema::table('in_app_notifications', function (Blueprint $table) {
            $table->string('dedupe_key', 80)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('in_app_notifications', function (Blueprint $table) {
            $table->dropUnique(['dedupe_key']);
            $table->dropColumn('dedupe_key');
        });
        Schema::dropIfExists('attendance_attempts');
        Schema::table('qr_tokens', function (Blueprint $table) {
            $table->dropUnique(['value']);
        });
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
