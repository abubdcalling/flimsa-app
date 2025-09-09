<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Drop foreign key first (assumes default name)
            $table->dropForeign(['device_id']);

            // Modify column to remove foreign key behavior
            $table->unsignedBigInteger('device_id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
    }
};

