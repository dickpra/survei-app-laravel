<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_add_is_public_to_surveys_table.php
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            //
        });
    }
};
