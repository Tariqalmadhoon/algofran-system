<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('centers')
            ->where('code', 'MAIN')
            ->whereIn('name', ['مركز الفرقان', 'مركز القرآن الكريم'])
            ->update(['name' => 'مركز الغفران لتحفيظ القرآن الكريم', 'updated_at' => now()]);

        DB::table('cms_contents')
            ->where('slug', 'about')
            ->whereIn('title', ['عن مركز الفرقان', 'عن مركز القرآن الكريم'])
            ->update(['title' => 'عن مركز الغفران لتحفيظ القرآن الكريم', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Branding changes are intentionally retained to avoid overwriting later editorial updates.
    }
};
