<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_groups', function (Blueprint $table) {
            $table->enum('type', ['aset', 'bahan_habis_pakai'])
                ->default('aset')
                ->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('item_groups', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
