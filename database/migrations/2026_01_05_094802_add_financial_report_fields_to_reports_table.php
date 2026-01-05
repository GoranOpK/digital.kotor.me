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
        Schema::table('reports', function (Blueprint $table) {
            // Polja specifična za finansijski izvještaj (Obrazac 4a)
            if (!Schema::hasColumn('reports', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->nullable()->after('approved_amount')->comment('Iznos ukupnih sredstava za realizaciju biznis plana');
            }
            if (!Schema::hasColumn('reports', 'report_date')) {
                $table->date('report_date')->nullable()->after('report_period_end')->comment('Datum popunjavanja izvještaja');
            }
            if (!Schema::hasColumn('reports', 'purchases_table')) {
                $table->text('purchases_table')->nullable()->after('description')->comment('Tabela sa nabavkama (JSON)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
            if (Schema::hasColumn('reports', 'report_date')) {
                $table->dropColumn('report_date');
            }
            if (Schema::hasColumn('reports', 'purchases_table')) {
                $table->dropColumn('purchases_table');
            }
        });
    }
};
