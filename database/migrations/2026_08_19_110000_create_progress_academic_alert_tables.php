<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_progress_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('as_of_date');
            $table->unsignedSmallInteger('last_memorized_ayah_id')->nullable();
            $table->unsignedSmallInteger('memorized_ayahs')->default(0);
            $table->decimal('memorized_percentage', 6, 3)->default(0);
            $table->unsignedSmallInteger('completed_surahs')->default(0);
            $table->unsignedTinyInteger('completed_juz')->default(0);
            $table->unsignedSmallInteger('memorization_sessions')->default(0);
            $table->unsignedSmallInteger('revision_sessions')->default(0);
            $table->date('last_revision_at')->nullable();
            $table->decimal('evaluation_average', 5, 2)->default(0);
            $table->decimal('performance_trend', 6, 2)->default(0);
            $table->decimal('attendance_rate', 5, 2)->default(0);
            $table->decimal('score', 5, 2)->default(0)->index();
            $table->json('score_breakdown');
            $table->json('metrics')->nullable();
            $table->timestamps();
            $table->foreign('last_memorized_ayah_id')->references('id')->on('quran_ayahs')->nullOnDelete();
            $table->unique(['student_id', 'as_of_date']);
            $table->index(['as_of_date', 'score']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('teacher_profiles')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->decimal('hours', 6, 2)->default(0);
            $table->string('status', 20)->default('planned')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['branch_id', 'starts_at']);
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('enrolled_at');
            $table->string('status', 20)->default('enrolled')->index();
            $table->decimal('result', 5, 2)->nullable();
            $table->string('grade', 50)->nullable();
            $table->date('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['course_id', 'student_id']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('private_file_id')->nullable()->constrained('private_files')->nullOnDelete();
            $table->string('name');
            $table->string('issuer');
            $table->string('certificate_number', 100)->nullable()->unique();
            $table->date('issued_at');
            $table->date('expires_at')->nullable();
            $table->string('grade', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'issued_at']);
        });

        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint')->nullable()->unique();
            $table->string('type', 30)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('achieved_at');
            $table->string('issuer')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'achieved_at']);
        });

        Schema::create('student_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('halaqa_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fingerprint')->unique();
            $table->string('type', 50)->index();
            $table->string('severity', 20)->index();
            $table->text('reason');
            $table->string('status', 20)->default('open')->index();
            $table->json('evidence')->nullable();
            $table->unsignedSmallInteger('occurrence_count')->default(1);
            $table->timestamp('generated_at')->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'status', 'severity']);
            $table->index(['halaqa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_alerts');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('student_progress_snapshots');
    }
};
