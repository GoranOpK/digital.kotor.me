<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            // Dodaj kolonu za putanju do izvornog fajla
            $table->string('original_file_path')->nullable()->after('file_path');
            
            // Dodaj kolonu za datum obrade
            $table->timestamp('processed_at')->nullable()->after('expires_at');
        });

        // Ažuriraj status enum - MySQL ne podržava direktnu promenu enum-a
        // Koristimo DB raw query
        DB::statement("ALTER TABLE user_documents MODIFY COLUMN status ENUM('pending', 'processing', 'processed', 'active', 'failed', 'expired') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            $table->dropColumn(['original_file_path', 'processed_at']);
        });

        // Vrati status enum na originalnu vrednost
        DB::statement("ALTER TABLE user_documents MODIFY COLUMN status ENUM('active', 'expired') DEFAULT 'active'");
    }
};

