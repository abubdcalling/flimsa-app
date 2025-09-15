<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->after('title');

            // Composite unique index (unique per season)
            $table->unique(['season_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropUnique(['season_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};
