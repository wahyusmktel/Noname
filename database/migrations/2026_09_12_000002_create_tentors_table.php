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
        Schema::create('tentors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title_prefix', 50)->nullable(); // Gelar depan (Dr., Drs., dll)
            $table->string('name'); // Nama lengkap
            $table->string('title_suffix', 50)->nullable(); // Gelar belakang (S.Pd., M.Si., dll)
            $table->string('phone', 25)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('photo')->nullable(); // Foto profil
            $table->string('specialization', 100)->nullable(); // Bidang pengajaran (opsional)
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tentors');
    }
};
