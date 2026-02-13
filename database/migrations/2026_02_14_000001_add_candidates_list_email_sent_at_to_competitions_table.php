<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (!Schema::hasColumn('competitions', 'candidates_list_email_sent_at')) {
                $table->timestamp('candidates_list_email_sent_at')->nullable()->after('closed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (Schema::hasColumn('competitions', 'candidates_list_email_sent_at')) {
                $table->dropColumn('candidates_list_email_sent_at');
            }
        });
    }
};
