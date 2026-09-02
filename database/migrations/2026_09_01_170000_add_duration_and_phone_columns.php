<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->string('usage_duration', 30)->nullable()->after('status');
            $table->dateTime('due_at')->nullable()->after('borrowed_at');
            $table->timestamp('overdue_notified_at')->nullable()->after('due_at');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['usage_duration', 'due_at', 'overdue_notified_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone']);
        });
    }
};
