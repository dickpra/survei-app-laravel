<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaire_templates', function (Blueprint $table) {
            // Hapus kolom lama jika ada
            if (Schema::hasColumn('questionnaire_templates', 'content_blocks')) {
                $table->dropColumn('content_blocks');
            }

            // Tambahkan kolom baru
            $table->string('demographic_title')->default('Data Diri')->after('description');
            $table->json('demographic_questions')->nullable()->after('demographic_title');
            $table->string('likert_title')->default('Kuesioner Inti')->after('demographic_questions');
            $table->json('likert_questions')->nullable()->after('likert_title');
        });
    }

    public function down(): void
    {
        Schema::table('questionnaire_templates', function (Blueprint $table) {
            $table->dropColumn([
                'demographic_title', 
                'demographic_questions', 
                'likert_title', 
                'likert_questions'
            ]);
            // Jika ingin mengembalikan kolom lama saat rollback
            $table->json('content_blocks')->nullable();
        });
    }
};