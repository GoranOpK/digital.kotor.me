<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'bonus_training')) {
                $table->boolean('bonus_training')
                    ->default(false)
                    ->after('final_score')
                    ->comment('Dodatni bod za prisustvovanje obuci za pisanje biznis plana (Info dan Opštine Kotor)');
            }

            if (!Schema::hasColumn('applications', 'bonus_women_business_mark')) {
                $table->boolean('bonus_women_business_mark')
                    ->default(false)
                    ->after('bonus_training')
                    ->comment('Dodatni bod za posjedovanje žiga „Ženski biznis“');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'bonus_training')) {
                $table->dropColumn('bonus_training');
            }
            if (Schema::hasColumn('applications', 'bonus_women_business_mark')) {
                $table->dropColumn('bonus_women_business_mark');
            }
        });
    }
};

