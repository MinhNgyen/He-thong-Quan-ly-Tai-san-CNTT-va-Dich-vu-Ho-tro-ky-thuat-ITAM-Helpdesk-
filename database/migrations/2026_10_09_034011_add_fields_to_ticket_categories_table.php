
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn(['name', 'description', 'is_active']);
        });
    }
};
