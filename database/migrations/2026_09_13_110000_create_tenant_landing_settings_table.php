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
        Schema::create('tenant_landing_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->string('navbar_subtitle')->nullable();
            $table->json('hero_slides')->nullable();
            $table->json('quality_header')->nullable();
            $table->json('quality_items')->nullable();
            $table->json('parent_cta')->nullable();
            $table->json('tentor_cta')->nullable();
            $table->json('contact_section')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_landing_settings');
    }
};
