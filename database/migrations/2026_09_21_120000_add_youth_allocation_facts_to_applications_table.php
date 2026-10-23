<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ITS_USER_FK = 'app_youth_its_conf_by_user_fk';

    private const PRIOR_USER_FK = 'app_youth_prior_fund_conf_by_user_fk';

    /**
     * Youth allocation facts and 30/20/15 cap audit only.
     * Nullable for existing and women's rows. No backfill.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->boolean('youth_innovative_tech_startup')->nullable();
            $table->timestamp('youth_innovative_tech_startup_confirmed_at')->nullable();
            $table->unsignedBigInteger('youth_innovative_tech_startup_confirmed_by_user_id')->nullable();
            $table->boolean('youth_prior_municipal_youth_funding')->nullable();
            $table->timestamp('youth_prior_municipal_youth_funding_confirmed_at')->nullable();
            $table->unsignedBigInteger('youth_prior_municipal_youth_funding_confirmed_by_user_id')->nullable();
            $table->unsignedTinyInteger('youth_applied_cap_percent')->nullable();

            $table->foreign('youth_innovative_tech_startup_confirmed_by_user_id', self::ITS_USER_FK)
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('youth_prior_municipal_youth_funding_confirmed_by_user_id', self::PRIOR_USER_FK)
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(self::ITS_USER_FK);
            $table->dropForeign(self::PRIOR_USER_FK);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'youth_innovative_tech_startup',
                'youth_innovative_tech_startup_confirmed_at',
                'youth_innovative_tech_startup_confirmed_by_user_id',
                'youth_prior_municipal_youth_funding',
                'youth_prior_municipal_youth_funding_confirmed_at',
                'youth_prior_municipal_youth_funding_confirmed_by_user_id',
                'youth_applied_cap_percent',
            ]);
        });
    }
};
