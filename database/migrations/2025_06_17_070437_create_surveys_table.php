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
        // database/migrations/xxxx_create_surveys_table.php
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('questionnaire_template_id')->constrained('questionnaire_templates')->onDelete('cascade');
            $table->string('title'); // Judul spesifik yang diberikan user untuk surveinya
            $table->string('unique_code')->unique(); // Kode unik seperti SURV-ABC123 [cite: 7]
            $table->boolean('enforce_single_submission')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
