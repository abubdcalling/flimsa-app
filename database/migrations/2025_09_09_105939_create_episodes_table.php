<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('series')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->unsignedInteger('episode_number');
            $table->string('title');
            $table->text('synopsis')->nullable();
            $table->unsignedInteger('runtime_minutes')->nullable();
            $table->date('release_date')->nullable();
            $table->enum('status', ['draft','scheduled','published','archived'])->default('published');

            // link to your MAIN table (contents)
            $table->foreignId('content_id')->nullable()->constrained('contents')->nullOnDelete();

            $table->timestamps();

            $table->unique(['season_id','episode_number']); // one E## per season
            $table->unique(['content_id']);                 // one content -> one episode (allows multiple NULLs)
            $table->index(['series_id','status']);
            $table->index(['season_id','release_date']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('episodes');
    }
};
