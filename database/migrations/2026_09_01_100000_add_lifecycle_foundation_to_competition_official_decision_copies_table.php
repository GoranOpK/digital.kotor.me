<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_official_decision_copies', function (Blueprint $table) {
            $table->string('storage_path')->nullable()->change();
            $table->string('business_title', 255)->nullable()->after('uploaded_by');
            $table->date('business_published_on')->nullable()->after('business_title');
            $table->timestamp('permanent_delete_pending_at')->nullable()->after('business_published_on');
            $table->timestamp('permanently_deleted_at')->nullable()->after('permanent_delete_pending_at');
            $table->unsignedBigInteger('permanently_deleted_by')->nullable()->after('permanently_deleted_at');

            $table->foreign('permanently_deleted_by', 'codc_permanently_deleted_by_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('competition_official_decision_copies', function (Blueprint $table) {
            $table->dropForeign('codc_permanently_deleted_by_fk');
            $table->dropColumn([
                'business_title',
                'business_published_on',
                'permanent_delete_pending_at',
                'permanently_deleted_at',
                'permanently_deleted_by',
            ]);
            $table->string('storage_path')->nullable(false)->change();
        });
    }
};
