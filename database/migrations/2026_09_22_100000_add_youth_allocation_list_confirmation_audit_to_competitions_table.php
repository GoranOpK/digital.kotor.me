<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const USER_FK = 'comp_yalc_user_fk';

    private const MEMBER_FK = 'comp_yalc_member_fk';

    /**
     * Youth-only final list confirmation audit on the Call.
     * Nullable for existing and women's rows. No backfill.
     */
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->timestamp('youth_allocation_list_confirmed_at')->nullable();
            $table->unsignedBigInteger('youth_allocation_list_confirmed_by_user_id')->nullable();
            $table->unsignedBigInteger('youth_allocation_list_confirmed_by_commission_member_id')->nullable();
            $table->string('youth_allocation_list_confirmed_by_name')->nullable();

            $table->foreign('youth_allocation_list_confirmed_by_user_id', self::USER_FK)
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('youth_allocation_list_confirmed_by_commission_member_id', self::MEMBER_FK)
                ->references('id')
                ->on('commission_members')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropForeign(self::USER_FK);
            $table->dropForeign(self::MEMBER_FK);
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn([
                'youth_allocation_list_confirmed_at',
                'youth_allocation_list_confirmed_by_user_id',
                'youth_allocation_list_confirmed_by_commission_member_id',
                'youth_allocation_list_confirmed_by_name',
            ]);
        });
    }
};
