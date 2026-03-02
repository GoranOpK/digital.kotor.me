<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rok za prijave na konkurs promijenjen sa 15 na 20 dana.
     * Postojeći konkursi: postavi deadline_days=20 i preračunaj end_date.
     */
    public function up(): void
    {
        $table = 'competitions';
        if (!Schema::hasColumn($table, 'deadline_days')) {
            return;
        }

        // Svi konkursi koriste 20 dana za rok prijave
        DB::table($table)->where(function ($q) {
            $q->where('deadline_days', 15)->orWhereNull('deadline_days');
        })->update(['deadline_days' => 20]);

        // Opciono: preračunaj end_date gdje postoji start_date (start_date + 20 dana)
        if (Schema::hasColumn($table, 'start_date') && Schema::hasColumn($table, 'end_date')) {
            $rows = DB::table($table)
                ->whereNotNull('start_date')
                ->select('id', 'start_date')
                ->get();
            foreach ($rows as $row) {
                $start = \Carbon\Carbon::parse($row->start_date);
                $newEnd = $start->copy()->addDays(20)->format('Y-m-d');
                DB::table($table)->where('id', $row->id)->update(['end_date' => $newEnd]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne vraćamo na 15 jer je poslovna odluka 20 dana
    }
};
