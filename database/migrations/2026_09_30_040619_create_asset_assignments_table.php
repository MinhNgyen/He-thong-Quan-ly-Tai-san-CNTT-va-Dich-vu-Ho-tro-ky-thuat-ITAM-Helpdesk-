<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
                ->constrained('assets')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('assigned_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('assigned_at');

            $table->dateTime('returned_at')
                ->nullable();

            $table->text('note')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};