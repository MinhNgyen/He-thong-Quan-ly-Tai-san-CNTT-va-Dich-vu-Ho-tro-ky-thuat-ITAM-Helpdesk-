<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->string('asset_code', 50)->unique();

            $table->string('name', 150);

            $table->foreignId('category_id')
                ->constrained('asset_categories')
                ->restrictOnDelete();

            $table->string('serial_number', 100)
                ->nullable()
                ->unique();

            $table->string('brand', 100)
                ->nullable();

            $table->string('model', 100)
                ->nullable();

            $table->date('purchase_date')
                ->nullable();

            $table->decimal('purchase_price', 15, 2)
                ->nullable();

            $table->enum('status', [
                'available',
                'in_use',
                'maintenance',
                'retired'
            ])->default('available');

            $table->text('description')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};