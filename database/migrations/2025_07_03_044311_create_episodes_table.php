<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('season_id');      // belongs to a season
            $table->unsignedBigInteger('content_id')->unique(); // one-to-one content
            $table->integer('episode_number')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('season_id')
                  ->references('id')
                  ->on('seasons')
                  ->onDelete('cascade');

            $table->foreign('content_id')
                  ->references('id')
                  ->on('contents')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
