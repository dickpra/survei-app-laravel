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
        Schema::table('users', function (Blueprint $table) {
            // Kolom ini akan menyimpan tanggal saat user mengajukan peran creator
            $table->timestamp('requested_creator_at')->nullable()->after('is_researcher');
            // Kolom ini untuk pengajuan peran researcher
            $table->timestamp('requested_researcher_at')->nullable()->after('requested_creator_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
