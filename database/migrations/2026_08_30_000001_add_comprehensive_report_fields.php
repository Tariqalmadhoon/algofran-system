<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('sponsorship_type')->nullable()->after('contact_phone');
            $table->string('sponsorship_organization')->nullable()->after('sponsorship_type');
        });

        Schema::table('teacher_profiles', function (Blueprint $table): void {
            $table->string('identity_number', 50)->nullable()->unique()->after('employee_number');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table): void {
            $table->dropUnique(['identity_number']);
            $table->dropColumn('identity_number');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn(['sponsorship_type', 'sponsorship_organization']);
        });
    }
};
