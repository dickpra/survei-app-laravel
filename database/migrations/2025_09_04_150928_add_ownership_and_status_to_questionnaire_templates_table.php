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
        Schema::table('questionnaire_templates', function (Blueprint $table) {
            // Kolom untuk melacak siapa yang membuat template
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->after('id');

            // Kolom untuk status persetujuan. Null berarti draft/belum disetujui.
            $table->timestamp('published_at')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questionnaire_templates', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('published_at');
        });
    }
};
