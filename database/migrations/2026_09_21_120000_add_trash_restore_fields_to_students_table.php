<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('pre_archive_status', 20)->nullable()->after('status');
        });

        // Earlier releases marked an archived student by status only. Move those
        // records into the real trash so they are not mixed with active searches.
        DB::table('students')
            ->where('status', 'archived')
            ->whereNull('deleted_at')
            ->update([
                'pre_archive_status' => 'active',
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('pre_archive_status');
        });
    }
};
