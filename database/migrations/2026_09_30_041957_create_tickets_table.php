<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('ticket_code', 50)->unique();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('category_id')
                ->constrained('ticket_categories')
                ->restrictOnDelete();

            $table->foreignId('asset_id')
                ->nullable()
                ->constrained('assets')
                ->nullOnDelete();

            $table->string('title', 200);

            $table->text('description');

            $table->enum('priority', [
                'low',
                'medium',
                'high',
                'urgent'
            ])->default('medium');

            $table->enum('status', [
                'open',
                'assigned',
                'in_progress',
                'resolved',
                'closed',
                'cancelled'
            ])->default('open');

            $table->timestamp('resolved_at')->nullable();

            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};