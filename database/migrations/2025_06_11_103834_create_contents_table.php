

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->json('video1')->nullable();
            $table->longText('title');
            $table->longText('description');
            $table->enum('publish', ['public', 'private', 'schedule'])->default('private')->index();
            $table->dateTime('schedule')->nullable();  // Nullable because only used if 'schedule' publish type
         $table->foreignId('genre_id')->nullable()->constrained('genres')->onDelete('cascade')->index();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
