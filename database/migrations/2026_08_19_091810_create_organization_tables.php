<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['center_id', 'code']);
        });

        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('center_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_number', 50)->unique();
            $table->string('job_title');
            $table->date('hired_at')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('center_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_number', 50)->unique();
            $table->string('specialization')->nullable();
            $table->date('hired_at')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('halaqas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('primary_teacher_id')->nullable()->constrained('teacher_profiles')->nullOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->string('program')->nullable();
            $table->unsignedSmallInteger('capacity')->default(20);
            $table->string('room')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->date('start_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['branch_id', 'code']);
            $table->index(['branch_id', 'active']);
        });

        Schema::create('halaqa_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('halaqa_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room')->nullable();
            $table->timestamps();
            $table->unique(['halaqa_id', 'weekday', 'starts_at']);
        });

        Schema::create('halaqa_teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('halaqa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_profile_id')->constrained()->restrictOnDelete();
            $table->string('role', 20)->default('primary');
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['halaqa_id', 'teacher_profile_id', 'starts_at'], 'halaqa_teacher_start_unique');
            $table->index(['halaqa_id', 'role', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('halaqa_teacher_assignments');
        Schema::dropIfExists('halaqa_schedules');
        Schema::dropIfExists('halaqas');
        Schema::dropIfExists('teacher_profiles');
        Schema::dropIfExists('staff_profiles');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('centers');
    }
};
