<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('dashboard_settings', function (Blueprint $table) {
            $table->id();
            // Hero
            $table->string('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();

            // Builder blocks (pakai JSON)
            $table->json('about_me')->nullable();
            $table->json('credit')->nullable();
            $table->json('guidebook')->nullable();
            $table->json('metodologi')->nullable();

            // Kontak
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('dashboard_settings');
    }
};
