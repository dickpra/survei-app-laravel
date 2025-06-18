<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('questionnaire_templates', function (Blueprint $table) {
            // Kolom ini akan menyimpan semua blok bagian dan pertanyaan dalam format JSON
            $table->json('content_blocks')->nullable()->after('description');
        });
    }
    public function down(): void {
        Schema::table('questionnaire_templates', function (Blueprint $table) {
            $table->dropColumn('content_blocks');
        });
    }
};