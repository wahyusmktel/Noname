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
        Schema::table('study_groups', function (Blueprint $table) {
            $table->string('education_level', 10)->default('SD')->after('name');
            $table->index(['tenant_id', 'academic_year_id', 'education_level'], 'study_groups_level_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_groups', function (Blueprint $table) {
            $table->dropIndex('study_groups_level_idx');
            $table->dropColumn('education_level');
        });
    }
};
