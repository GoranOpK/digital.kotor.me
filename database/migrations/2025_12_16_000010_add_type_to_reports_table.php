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
            if (!Schema::hasColumn('reports', 'type')) {
                $table->enum('type', ['realization', 'financial'])->default('realization')->after('application_id');
            }
            if (!Schema::hasColumn('reports', 'evaluation_notes')) {
                $table->text('evaluation_notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('reports', 'evaluated_at')) {
                $table->timestamp('evaluated_at')->nullable()->after('evaluation_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $columns = ['type', 'evaluation_notes', 'evaluated_at'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

