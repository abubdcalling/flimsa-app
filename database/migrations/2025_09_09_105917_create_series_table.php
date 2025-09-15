<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('release_date')->nullable();
            $table->enum('status', ['draft','active','archived'])->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index('release_date');
        });
    }

    public function down(): void {
        Schema::dropIfExists('series');
    }
};
