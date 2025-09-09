<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('serie_id'); // foreign key to series table
            $table->integer('season_number');
            $table->string('title')->nullable();
            $table->date('release_date')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('serie_id')
                ->references('id')
                ->on('series')
                ->onDelete('cascade'); // delete seasons if series is deleted
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
