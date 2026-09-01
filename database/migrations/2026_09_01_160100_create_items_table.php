<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique()->index();
            $table->string('epc_tag')->nullable()->unique()->index();
            $table->string('name');
            $table->enum('type', ['aset','bahan_habis_pakai']);
            $table->integer('stock')->default(1);
            $table->enum('status', ['tersedia','dipinjam','under_maintenance'])->default('tersedia');
            $table->string('location');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
