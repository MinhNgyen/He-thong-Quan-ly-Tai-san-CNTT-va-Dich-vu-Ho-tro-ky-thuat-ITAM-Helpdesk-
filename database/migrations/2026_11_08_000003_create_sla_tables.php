<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cam kết thời gian xử lý theo mức ưu tiên, tính bằng PHÚT LÀM VIỆC.
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('priority', 20)->unique();
            $table->string('name', 100);
            $table->unsignedInteger('resolution_minutes');
            $table->timestamps();
        });

        // Ngày nghỉ (lễ, tết) bị loại khỏi giờ làm việc khi tính hạn SLA.
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name', 150);
            $table->timestamps();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('sla_paused_at')->nullable();
            $table->unsignedInteger('sla_remaining_minutes')->nullable();
            $table->timestamp('sla_escalated_at')->nullable();
        });

        Schema::create('escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('reason', 255);
            $table->timestamp('created_at')->useCurrent();

            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escalations');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['sla_due_at', 'sla_paused_at', 'sla_remaining_minutes', 'sla_escalated_at']);
        });

        Schema::dropIfExists('holidays');
        Schema::dropIfExists('sla_policies');
    }
};
