<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preventive_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('interval_months');
            $table->date('last_serviced_at');
            $table->date('next_due_date')->index();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'paused'])->default('scheduled');
            $table->string('last_notified_stage', 20)->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preventive_schedules');
    }
};

