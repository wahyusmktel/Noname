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
            $table->string('phone_2', 25)->nullable()->after('phone');
            $table->string('tiktok_url', 255)->nullable()->after('website');
            $table->string('instagram_url', 255)->nullable()->after('tiktok_url');
            $table->string('youtube_url', 255)->nullable()->after('instagram_url');
            $table->string('facebook_url', 255)->nullable()->after('youtube_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'phone_2',
                'tiktok_url',
                'instagram_url',
                'youtube_url',
                'facebook_url',
            ]);
        });
    }
};
