<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('halaqa_id')->constrained()->restrictOnDelete();
            $table->date('absence_date');
            $table->text('reason');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['teacher_profile_id', 'halaqa_id', 'absence_date'],
                'teacher_absence_scope_unique'
            );
            $table->index(['halaqa_id', 'absence_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_absences');
    }
};
