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
        Schema::table('user_documents', function (Blueprint $table) {
            // Učini file_path nullable jer se kreira tek nakon obrade
            $table->string('file_path')->nullable()->change();
            
            // Učini file_size nullable jer se ažurira tek nakon obrade
            $table->bigInteger('file_size')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            // Vrati na obavezno (ali ovo može da padne ako postoje NULL vrednosti)
            $table->string('file_path')->nullable(false)->change();
            $table->bigInteger('file_size')->nullable(false)->change();
        });
    }
};

