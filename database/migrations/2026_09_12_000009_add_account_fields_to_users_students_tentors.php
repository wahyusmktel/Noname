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
        // 1. Kolom akun pada tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
            $table->string('email')->nullable()->change();
        });

        // 2. Kolom akun pada tabel students
        Schema::table('students', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->after('academic_year_id')->constrained('users')->nullOnDelete();
            $table->string('username', 50)->nullable()->index()->after('user_id');
            $table->string('plain_password', 100)->nullable()->after('username');
        });

        // 3. Kolom akun pada tabel tentors
        Schema::table('tentors', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->after('tenant_id')->constrained('users')->nullOnDelete();
            $table->string('username', 50)->nullable()->index()->after('user_id');
            $table->string('plain_password', 100)->nullable()->after('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tentors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'username', 'plain_password']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'username', 'plain_password']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
            $table->string('email')->nullable(false)->change();
        });
    }
};
