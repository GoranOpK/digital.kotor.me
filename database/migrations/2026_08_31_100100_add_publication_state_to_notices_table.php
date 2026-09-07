<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->boolean('publicly_available')->default(true)->after('visible_in_active_panel');
            $table->unsignedBigInteger('superseded_notice_id')->nullable()->after('source_id');
            $table->unsignedBigInteger('source_object_id')->nullable()->after('superseded_notice_id');

            $table->foreign('superseded_notice_id', 'notices_superseded_notice_fk')
                ->references('id')
                ->on('notices')
                ->restrictOnDelete();

            $table->foreign('source_object_id', 'notices_source_object_fk')
                ->references('id')
                ->on('competition_official_decision_copies')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropForeign('notices_source_object_fk');
            $table->dropForeign('notices_superseded_notice_fk');
            $table->dropColumn([
                'publicly_available',
                'superseded_notice_id',
                'source_object_id',
            ]);
        });
    }
};
