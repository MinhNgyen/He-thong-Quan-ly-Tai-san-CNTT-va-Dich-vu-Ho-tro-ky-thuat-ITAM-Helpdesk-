<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('action', 100);

            $table->string('old_status', 50)
                ->nullable();

            $table->string('new_status', 50)
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_histories');
    }
};