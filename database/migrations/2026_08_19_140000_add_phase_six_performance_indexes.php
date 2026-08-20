<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_alerts', function (Blueprint $table) {
            $table->index(['status', 'severity', 'generated_at'], 'alerts_dashboard_index');
        });

        Schema::table('calendar_events', function (Blueprint $table) {
            $table->index(['privacy', 'starts_at'], 'calendar_privacy_start_index');
        });

        Schema::table('cms_contents', function (Blueprint $table) {
            $table->index(['type', 'status', 'featured', 'published_at'], 'cms_public_feed_index');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->index(['status', 'starts_at'], 'courses_public_feed_index');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->index(['tokenable_type', 'tokenable_id', 'name'], 'tokens_device_index');
        });

        Schema::table('private_files', function (Blueprint $table) {
            $table->unique(['disk', 'path'], 'private_files_disk_path_unique');
        });
    }

    public function down(): void
    {
        Schema::table('student_alerts', function (Blueprint $table) {
            $table->dropIndex('alerts_dashboard_index');
        });

        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropIndex('calendar_privacy_start_index');
        });

        Schema::table('cms_contents', function (Blueprint $table) {
            $table->dropIndex('cms_public_feed_index');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_public_feed_index');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('tokens_device_index');
        });

        Schema::table('private_files', function (Blueprint $table) {
            $table->dropUnique('private_files_disk_path_unique');
        });
    }
};
