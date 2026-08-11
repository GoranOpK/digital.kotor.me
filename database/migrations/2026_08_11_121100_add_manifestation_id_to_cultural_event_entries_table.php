<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultural_event_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('manifestation_id')
                ->nullable()
                ->after('organizer_id');

            $table->foreign('manifestation_id', 'cee_manifestation_fk')
                ->references('id')->on('cultural_manifestations')
                ->nullOnDelete();

            $table->index('manifestation_id');
        });
    }

    public function down(): void
    {
        Schema::table('cultural_event_entries', function (Blueprint $table) {
            $table->dropForeign('cee_manifestation_fk');
            $table->dropIndex(['manifestation_id']);
            $table->dropColumn('manifestation_id');
        });
    }
};

