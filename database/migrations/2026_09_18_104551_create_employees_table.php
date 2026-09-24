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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
             $table->string('employee_no')->unique();

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');

            $table->string('phone')->nullable();
            $table->string('email')->nullable()->unique();

            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('job_title')->nullable();

            $table->foreignId('supervisor_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->string('profile_photo')->nullable();

            $table->enum('employment_status', [
                'active',
                'inactive',
                'terminated',
                'retired'
            ])->default('active');

            $table->timestamps();

            $table->index([
                'department_id',
                'unit_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
