<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->string('vendor_name', 150)->nullable()->after('service_type');
            $table->text('spareparts_used')->nullable()->after('action_taken');
            $table->string('receipt_path')->nullable()->after('cost');
            $table->date('estimated_finish_at')->nullable()->after('repaired_at');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropColumn([
                'vendor_name',
                'spareparts_used',
                'receipt_path',
                'estimated_finish_at',
            ]);
        });
    }
};
