<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
                ->constrained('assets')
                ->restrictOnDelete();

            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title', 150);

            $table->text('description')->nullable();

            $table->date('maintenance_date');

            $table->decimal('cost', 15, 2)->default(0);

            $table->enum('status', [
                'pending',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('pending');

            $table->date('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};