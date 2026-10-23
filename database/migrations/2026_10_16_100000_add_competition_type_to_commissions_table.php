<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill `commissions.profile_type` for homogeneous ženske/omladinske Komisije.
 * Origin already owns the column; this migration must not add `competition_type`.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const BACKFILL_TYPES = ['zensko', 'omladinsko'];

    public function up(): void
    {
        if (! Schema::hasColumn('commissions', 'profile_type')) {
            return;
        }

        $assigned = DB::table('competitions')
            ->select('commission_id', 'type')
            ->whereNotNull('commission_id')
            ->get()
            ->groupBy('commission_id');

        foreach ($assigned as $commissionId => $rows) {
            $types = $rows->pluck('type')->unique()->values();
            if ($types->count() !== 1) {
                continue;
            }

            $type = $types->first();
            if (! in_array($type, self::BACKFILL_TYPES, true)) {
                continue;
            }

            DB::table('commissions')
                ->where('id', $commissionId)
                ->whereNull('profile_type')
                ->update(['profile_type' => $type]);
        }
    }

    public function down(): void
    {
        // Intentionally irreversible no-op. Origin owns `commissions.profile_type`.
        // Do not drop the column, rewind canonical rows, or NULL out zensko/omladinsko
        // values written after deploy (including 6.20.1 creates).
    }
};
