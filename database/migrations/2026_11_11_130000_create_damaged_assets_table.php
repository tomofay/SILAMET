<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damaged_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('item_code', 50);
            $table->string('item_name', 150);
            $table->string('item_category', 80)->nullable();
            $table->string('item_type', 30)->default('aset');
            $table->string('epc_tag', 100)->nullable();
            $table->string('location', 100)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('source', 30)->index(); // repair_ticket | preventive_maintenance
            $table->foreignId('maintenance_ticket_id')->nullable()->constrained('maintenance_tickets')->nullOnDelete();
            $table->foreignId('preventive_schedule_id')->nullable()->constrained('preventive_schedules')->nullOnDelete();
            $table->text('damage_reason');
            $table->string('service_type', 30)->nullable();
            $table->string('vendor_name', 150)->nullable();
            $table->decimal('repair_cost', 12, 2)->default(0);
            $table->string('receipt_path', 255)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('damaged_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damaged_assets');
    }
};
