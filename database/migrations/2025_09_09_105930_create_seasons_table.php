<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('series')->cascadeOnDelete();
            $table->unsignedInteger('season_number');
            $table->string('title')->nullable();
            $table->date('release_date')->nullable();
            $table->enum('status', ['draft','active','archived'])->default('active');
            $table->timestamps();

            $table->unique(['series_id','season_number']); // one S## per series
            $table->index(['series_id','status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('seasons');
    }
};
