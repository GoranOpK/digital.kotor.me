<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->string('cloud_path')->nullable()->after('file_path');
            $table->string('mega_node_id')->nullable()->after('cloud_path');
            $table->string('mega_file_name')->nullable()->after('mega_node_id');
        });
    }

    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropColumn(['cloud_path', 'mega_node_id', 'mega_file_name']);
        });
    }
};
