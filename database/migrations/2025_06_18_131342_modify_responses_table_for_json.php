<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('responses', function (Blueprint $table) {
            // Kolom ini akan menyimpan semua jawaban dari satu responden
            $table->json('answers')->nullable()->after('survey_id');
        });
    }
    public function down(): void {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn('answers');
        });
    }
};