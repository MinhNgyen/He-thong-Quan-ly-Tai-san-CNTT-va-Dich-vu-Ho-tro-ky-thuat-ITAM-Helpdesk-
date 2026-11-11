<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cây tài sản cha - con nhiều cấp (máy chủ > thiết bị lưu trữ > ổ đĩa...).
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
