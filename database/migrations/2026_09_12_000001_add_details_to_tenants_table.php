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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('name');
            $table->text('description')->nullable()->after('tagline');
            $table->string('province', 100)->nullable()->after('city');
            $table->string('postal_code', 10)->nullable()->after('province');
            $table->string('website')->nullable()->after('email');
            $table->string('operating_hours')->nullable()->after('address');
            $table->string('whatsapp_sender', 25)->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'tagline',
                'description',
                'province',
                'postal_code',
                'website',
                'operating_hours',
                'whatsapp_sender',
            ]);
        });
    }
};
