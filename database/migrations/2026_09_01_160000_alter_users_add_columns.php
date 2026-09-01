<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nis_nip')->after('name')->nullable()->unique();
            $table->enum('role', ['siswa','teknisi','kabeng','kaprog'])->default('siswa');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nis_nip']);
            $table->dropColumn(['nis_nip', 'role']);
            $table->dropSoftDeletes();
        });
    }
};
