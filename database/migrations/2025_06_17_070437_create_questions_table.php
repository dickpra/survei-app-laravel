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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_template_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->string('section')->nullable();
            $table->string('type'); // 'multiple_choice', 'likert', 'short_text'
            $table->json('options')->nullable(); // Untuk pilihan ganda atau skala likert
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
