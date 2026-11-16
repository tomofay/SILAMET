<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('borrowed_via', 20)->default('manual')->after('status');
        });

        // Set historical kiosk loans that have a capture image
        DB::table('loans')
            ->whereNotNull('capture_image_path')
            ->where('capture_image_path', '!=', '')
            ->update(['borrowed_via' => 'kiosk']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('borrowed_via');
        });
    }
};
