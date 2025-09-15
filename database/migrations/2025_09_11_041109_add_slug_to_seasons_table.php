<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->after('title');

            // Composite unique index (per series_id)
            $table->unique(['series_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropUnique(['series_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};
