<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolone sa dugim tekstom (textarea polja) – TEXT (~65 KB) je premali za
     * duže unose, npr. competition_analysis spaja dva polja u jednu kolonu.
     */
    private array $columns = [
        'summary',
        'promotion',
        'competition_analysis',
        'business_analysis',
        'required_resources',
        'work_experience',
        'personal_strengths_weaknesses',
    ];

    public function up(): void
    {
        foreach ($this->columns as $column) {
            if (Schema::hasColumn('business_plans', $column)) {
                DB::statement("ALTER TABLE business_plans MODIFY {$column} MEDIUMTEXT NULL");
            }
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $column) {
            if (Schema::hasColumn('business_plans', $column)) {
                DB::statement("ALTER TABLE business_plans MODIFY {$column} TEXT NULL");
            }
        }
    }
};
