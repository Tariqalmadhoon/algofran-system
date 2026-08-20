<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_surahs', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('name_arabic', 100);
            $table->unsignedSmallInteger('verses_count');
            $table->string('revelation_place', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('quran_ayahs', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->primary();
            $table->unsignedTinyInteger('surah_id');
            $table->unsignedSmallInteger('ayah_number');
            $table->unsignedSmallInteger('global_order')->unique();
            $table->unsignedTinyInteger('juz')->nullable()->index();
            $table->unsignedTinyInteger('hizb')->nullable()->index();
            $table->unsignedSmallInteger('page')->nullable()->index();
            $table->timestamps();
            $table->foreign('surah_id')->references('id')->on('quran_surahs')->restrictOnDelete();
            $table->unique(['surah_id', 'ayah_number']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('student_number', 50)->unique();
            $table->string('first_name', 100);
            $table->string('father_name', 100);
            $table->string('grandfather_name', 100);
            $table->string('family_name', 100);
            $table->string('full_name')->index();
            $table->string('identity_number', 50)->nullable()->unique();
            $table->date('birth_date')->nullable();
            $table->string('contact_phone', 30)->nullable()->index();
            $table->date('registration_date')->index();
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('current_halaqa_id')->nullable()->constrained('halaqas')->nullOnDelete();
            $table->foreignId('photo_private_file_id')->nullable()->constrained('private_files')->nullOnDelete();
            $table->foreignId('identity_private_file_id')->nullable()->constrained('private_files')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['current_halaqa_id', 'status']);
        });

        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('identity_number', 50)->nullable()->unique();
            $table->string('phone', 30)->index();
            $table->string('alternative_phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->foreignId('identity_private_file_id')->nullable()->constrained('private_files')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 30);
            $table->boolean('is_primary')->default(false);
            $table->boolean('can_receive_notifications')->default(true);
            $table->timestamps();
            $table->unique(['guardian_id', 'student_id']);
            $table->index(['student_id', 'is_primary']);
        });

        Schema::create('halaqa_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('halaqa_id')->constrained()->restrictOnDelete();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'halaqa_id', 'starts_at'], 'student_halaqa_start_unique');
            $table->index(['student_id', 'ends_at']);
            $table->index(['halaqa_id', 'starts_at', 'ends_at']);
        });

        Schema::create('student_memorization_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('start_ayah_id');
            $table->unsignedSmallInteger('end_ayah_id');
            $table->date('recorded_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('start_ayah_id')->references('id')->on('quran_ayahs')->restrictOnDelete();
            $table->foreign('end_ayah_id')->references('id')->on('quran_ayahs')->restrictOnDelete();
            $table->index(['student_id', 'recorded_at']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('halaqa_id')->constrained()->restrictOnDelete();
            $table->date('record_date');
            $table->string('status', 20)->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'record_date']);
            $table->index(['halaqa_id', 'record_date', 'status']);
        });

        Schema::create('daily_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('halaqa_id')->constrained()->restrictOnDelete();
            $table->foreignId('attendance_id')->unique()->constrained('attendances')->cascadeOnDelete();
            $table->date('record_date');
            $table->string('general_evaluation', 20)->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'record_date']);
            $table->index(['halaqa_id', 'record_date']);
            $table->index(['teacher_profile_id', 'record_date']);
        });

        Schema::create('recitation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_record_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->index();
            $table->unsignedSmallInteger('start_ayah_id');
            $table->unsignedSmallInteger('end_ayah_id');
            $table->string('evaluation', 20);
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('memorization_errors')->default(0);
            $table->unsignedSmallInteger('tajweed_errors')->default(0);
            $table->unsignedSmallInteger('hesitation_count')->default(0);
            $table->unsignedSmallInteger('teacher_prompt_count')->default(0);
            $table->timestamps();
            $table->foreign('start_ayah_id')->references('id')->on('quran_ayahs')->restrictOnDelete();
            $table->foreign('end_ayah_id')->references('id')->on('quran_ayahs')->restrictOnDelete();
            $table->index(['type', 'evaluation']);
        });

        Schema::create('student_timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 50)->index();
            $table->nullableMorphs('source');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->index(['student_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_timeline_events');
        Schema::dropIfExists('recitation_items');
        Schema::dropIfExists('daily_records');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('student_memorization_baselines');
        Schema::dropIfExists('halaqa_enrollments');
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('students');
        Schema::dropIfExists('quran_ayahs');
        Schema::dropIfExists('quran_surahs');
    }
};
