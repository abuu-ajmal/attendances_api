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
        Schema::create('user_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('scope_type', [
                'organization',
                'department',
                'unit',
            ]);

            $table->unsignedBigInteger('scope_id')->nullable();

            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
            $table->unique([
                'user_id',
                'scope_type',
                'scope_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_scopes');
    }
};
