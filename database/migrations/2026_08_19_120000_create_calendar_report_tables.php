<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('halaqa_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->string('privacy', 20)->default('internal')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['starts_at', 'ends_at']);
            $table->index(['halaqa_id', 'starts_at']);
        });

        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report_type', 40)->index();
            $table->string('format', 10)->default('xlsx');
            $table->json('filters')->nullable();
            $table->string('status', 20)->default('preparing')->index();
            $table->foreignId('private_file_id')->nullable()->constrained('private_files')->nullOnDelete();
            $table->unsignedInteger('rows_count')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('uploaded_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type', 40)->index();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('privacy', 20)->default('internal')->index();
            $table->text('notes')->nullable();
            $table->foreignId('private_file_id')->nullable()->constrained('private_files')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_reports');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('calendar_events');
    }
};
