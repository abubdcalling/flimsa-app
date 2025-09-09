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
        Schema::table('subtitles', function (Blueprint $table) {
            $table->unique(['content_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subtitles', function (Blueprint $table) {
             $table->dropUnique(['subtitles_content_id_language_unique']);
        });
    }
};
