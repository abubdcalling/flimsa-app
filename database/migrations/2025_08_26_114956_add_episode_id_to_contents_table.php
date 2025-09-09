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
        Schema::table('contents', function (Blueprint $table) {
            $table->foreignId('episode_id')
                  ->nullable()
                  ->constrained()
                  ->cascadeOnDelete()
                  ->after('id'); // optional: position after id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropForeign(['episode_id']);
            $table->dropColumn('episode_id');
        });
    }
};
