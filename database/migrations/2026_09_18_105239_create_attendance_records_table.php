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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
             $table->uuid('uuid')->unique();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->enum('type', [
                'check_in',
                'check_out'
            ]);

            $table->dateTime('occurred_at');

            $table->dateTime('server_received_at')
                ->nullable();

            $table->decimal('latitude', 10, 7)
                ->nullable();

            $table->decimal('longitude', 10, 7)
                ->nullable();

            $table->decimal('accuracy', 10, 2)
                ->nullable();

            $table->string('photo_path')
                ->nullable();

            $table->string('device_id')
                ->nullable();

            $table->enum('sync_status', [
                'synced',
                'pending',
                'failed'
            ])->default('synced');

            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            $table->index([
                'employee_id',
                'occurred_at'
            ]);

            $table->index([
                'type',
                'occurred_at'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
