<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Potrebno za brisanje fajlova sa MEGA (megajs find + delete).
     */
    public function up(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            $table->string('mega_node_id')->nullable()->after('cloud_path');
            $table->string('mega_file_name')->nullable()->after('mega_node_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            $table->dropColumn(['mega_node_id', 'mega_file_name']);
        });
    }
};
