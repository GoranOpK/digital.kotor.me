<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline termina na update op-u — detekcija konflikta sa statusnim Odgođen→Planiran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cultural_event_change_proposal_occurrences', function (Blueprint $table) {
            $table->date('baseline_datum')->nullable()->after('source_occurrence_id');
            $table->time('baseline_vrijeme_od')->nullable()->after('baseline_datum');
            $table->time('baseline_vrijeme_do')->nullable()->after('baseline_vrijeme_od');
            $table->boolean('baseline_cjelodnevno')->nullable()->after('baseline_vrijeme_do');
        });
    }

    public function down(): void
    {
        Schema::table('cultural_event_change_proposal_occurrences', function (Blueprint $table) {
            $table->dropColumn([
                'baseline_datum',
                'baseline_vrijeme_od',
                'baseline_vrijeme_do',
                'baseline_cjelodnevno',
            ]);
        });
    }
};
