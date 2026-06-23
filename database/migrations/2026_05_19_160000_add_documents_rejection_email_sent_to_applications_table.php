<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'documents_rejection_email_sent')) {
                $table->unsignedTinyInteger('documents_rejection_email_sent')
                    ->default(0)
                    ->after('rejection_reason')
                    ->comment('0 = mail o nepotpunoj dokumentaciji nije poslat, 1 = poslat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'documents_rejection_email_sent')) {
                $table->dropColumn('documents_rejection_email_sent');
            }
        });
    }
};
