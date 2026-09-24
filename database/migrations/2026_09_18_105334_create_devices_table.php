<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
               $table->foreignId('employee_id')
        ->constrained()
        ->cascadeOnDelete();

        $table->string('device_uuid')->unique();

        $table->string('device_name')->nullable();
        $table->string('platform')->nullable();
        $table->string('os_version')->nullable();
        $table->string('app_version')->nullable();

        $table->dateTime('last_seen_at')
            ->nullable();

        $table->boolean('is_active')
        ->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
