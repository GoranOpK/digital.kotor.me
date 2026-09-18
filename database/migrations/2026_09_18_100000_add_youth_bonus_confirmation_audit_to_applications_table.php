<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const USER_FK = 'app_bonuses_conf_by_user_fk';

    private const MEMBER_FK = 'app_bonuses_conf_by_member_fk';

    /**
     * Youth bonus lock audit only. Does not backfill or change existing bonus flags.
     */
    public function up(): void
    {
        $missingColumns = [];
        foreach ([
            'bonuses_confirmed_at',
            'bonuses_confirmed_by_user_id',
            'bonuses_confirmed_by_commission_member_id',
            'bonuses_confirmed_by_name',
        ] as $column) {
            if (! Schema::hasColumn('applications', $column)) {
                $missingColumns[] = $column;
            }
        }

        if ($missingColumns !== []) {
            Schema::table('applications', function (Blueprint $table) use ($missingColumns) {
                if (in_array('bonuses_confirmed_at', $missingColumns, true)) {
                    $table->timestamp('bonuses_confirmed_at')->nullable();
                }
                if (in_array('bonuses_confirmed_by_user_id', $missingColumns, true)) {
                    $table->unsignedBigInteger('bonuses_confirmed_by_user_id')->nullable();
                }
                if (in_array('bonuses_confirmed_by_commission_member_id', $missingColumns, true)) {
                    $table->unsignedBigInteger('bonuses_confirmed_by_commission_member_id')->nullable();
                }
                if (in_array('bonuses_confirmed_by_name', $missingColumns, true)) {
                    $table->string('bonuses_confirmed_by_name')->nullable();
                }
            });
        }

        $missingForeigns = [];
        if (! $this->foreignExists(self::USER_FK)) {
            $missingForeigns[] = self::USER_FK;
        }
        if (! $this->foreignExists(self::MEMBER_FK)) {
            $missingForeigns[] = self::MEMBER_FK;
        }

        if ($missingForeigns !== []) {
            Schema::table('applications', function (Blueprint $table) use ($missingForeigns) {
                if (in_array(self::USER_FK, $missingForeigns, true)) {
                    $table->foreign('bonuses_confirmed_by_user_id', self::USER_FK)
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                }
                if (in_array(self::MEMBER_FK, $missingForeigns, true)) {
                    $table->foreign('bonuses_confirmed_by_commission_member_id', self::MEMBER_FK)
                        ->references('id')
                        ->on('commission_members')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        $dropUserFk = $this->foreignExists(self::USER_FK);
        $dropMemberFk = $this->foreignExists(self::MEMBER_FK);

        if ($dropUserFk || $dropMemberFk) {
            Schema::table('applications', function (Blueprint $table) use ($dropUserFk, $dropMemberFk) {
                if ($dropUserFk) {
                    $table->dropForeign(self::USER_FK);
                }
                if ($dropMemberFk) {
                    $table->dropForeign(self::MEMBER_FK);
                }
            });
        }

        $columns = [];
        foreach ([
            'bonuses_confirmed_at',
            'bonuses_confirmed_by_user_id',
            'bonuses_confirmed_by_commission_member_id',
            'bonuses_confirmed_by_name',
        ] as $column) {
            if (Schema::hasColumn('applications', $column)) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            Schema::table('applications', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    private function foreignExists(string $name): bool
    {
        $database = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$database, 'applications', $name, 'FOREIGN KEY']
        );

        return $row !== null;
    }
};
