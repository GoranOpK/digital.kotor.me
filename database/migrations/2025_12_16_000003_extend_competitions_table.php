<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            if (!Schema::hasColumn('competitions', 'competition_number')) {
                $table->string('competition_number')->nullable()->after('id'); // Broj konkursa (1. ili 2. u godini)
            }
            if (!Schema::hasColumn('competitions', 'year')) {
                $table->year('year')->nullable()->after('competition_number'); // Godina konkursa
            }
            if (!Schema::hasColumn('competitions', 'budget')) {
                $table->decimal('budget', 15, 2)->nullable()->after('year'); // Ukupan budžet za konkurs
            }
            if (!Schema::hasColumn('competitions', 'max_support_percentage')) {
                $table->decimal('max_support_percentage', 5, 2)->default(30.00)->after('budget'); // Maksimalno 30% po biznis planu
            }
            if (!Schema::hasColumn('competitions', 'deadline_days')) {
                $table->integer('deadline_days')->default(20)->after('max_support_percentage'); // Rok za prijave (20 dana)
            }
            if (!Schema::hasColumn('competitions', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('end_date'); // Datum objavljivanja
            }
            if (!Schema::hasColumn('competitions', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('published_at'); // Datum zatvaranja
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $columns = [
                'competition_number',
                'year',
                'budget',
                'max_support_percentage',
                'deadline_days',
                'published_at',
                'closed_at'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('competitions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

