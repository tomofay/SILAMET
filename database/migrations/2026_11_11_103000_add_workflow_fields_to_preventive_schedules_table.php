<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preventive_schedules', function (Blueprint $table) {
            $table->date('maintenance_date')->nullable()->after('last_serviced_at');
            $table->unsignedInteger('quantity')->default(1)->after('maintenance_date');
            $table->unsignedInteger('safe_quantity')->default(0)->after('quantity');
            $table->unsignedInteger('damaged_quantity')->default(0)->after('safe_quantity');
            $table->string('workflow_stage', 30)->default('scheduled')->index()->after('status');
            $table->text('damage_notes')->nullable()->after('notes');
            $table->foreignId('parent_schedule_id')->nullable()->after('item_id')->constrained('preventive_schedules')->nullOnDelete();
            $table->foreignId('maintenance_ticket_id')->nullable()->after('created_by')->constrained('maintenance_tickets')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable()->after('maintenance_ticket_id');
            $table->foreignId('inspected_by')->nullable()->after('inspected_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('preventive_schedules', function (Blueprint $table) {
            $table->dropForeign(['parent_schedule_id']);
            $table->dropForeign(['maintenance_ticket_id']);
            $table->dropForeign(['inspected_by']);
            $table->dropColumn([
                'maintenance_date',
                'quantity',
                'safe_quantity',
                'damaged_quantity',
                'workflow_stage',
                'damage_notes',
                'parent_schedule_id',
                'maintenance_ticket_id',
                'inspected_at',
                'inspected_by',
            ]);
        });
    }
};
