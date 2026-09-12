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
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('study_group_id')->nullable()->constrained('study_groups')->nullOnDelete();
            $table->string('name', 150); // Nama lengkap peserta didik
            $table->string('parent_phone', 25)->nullable(); // Nomor HP / WhatsApp orang tua
            $table->string('student_phone', 25)->nullable(); // Nomor HP / WhatsApp siswa
            $table->string('photo')->nullable(); // Foto profil peserta didik
            $table->enum('status', ['active', 'inactive'])->default('active'); // Aktif / Keluar dari bimbel
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'academic_year_id', 'status']);
            $table->index('study_group_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
