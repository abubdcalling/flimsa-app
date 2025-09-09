<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('histories', function (Blueprint $table) {
        $table->id();
        // optionally add user_id and content_id here right away
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('content_id')->constrained('contents')->onDelete('cascade');
        $table->timestamps();
    });
    }

    public function down(): void
    {
        Schema::table('histories', function (Blueprint $table) {
            

            // Then drop the columns
            $table->dropColumn(['user_id', 'content_id']);
        });
    }
};
