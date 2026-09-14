<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid');
            $table->string('name', 100);
            $table->string('platform', 30)->nullable();
            $table->string('app_version', 30)->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('disabled_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'uuid'], 'mobile_device_user_uuid_unique');
        });

        Schema::create('mobile_sync_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobile_device_id')->constrained('mobile_devices')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('operation_uuid');
            $table->string('operation_type', 50)->default('daily_record.create');
            $table->char('payload_hash', 64);
            $table->string('status', 30)->default('processing')->index();
            $table->foreignId('daily_record_id')->nullable()->constrained('daily_records')->nullOnDelete();
            $table->json('response')->nullable();
            $table->string('error_code', 80)->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamp('client_created_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['mobile_device_id', 'operation_uuid'], 'mobile_device_operation_unique');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_sync_operations');
        Schema::dropIfExists('mobile_devices');
    }
};
