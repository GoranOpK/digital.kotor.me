<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_VALUE = 'Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)';

    private const NEW_VALUE = 'Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)';

    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'user_type')) {
            DB::table('users')
                ->where('user_type', self::OLD_VALUE)
                ->update(['user_type' => self::NEW_VALUE]);

            try {
                DB::statement("ALTER TABLE `users` MODIFY COLUMN `user_type` ENUM(
                    'Fizičko lice',
                    'Preduzetnik',
                    'Ortačko društvo',
                    'Komanditno društvo',
                    'Društvo sa ograničenom odgovornošću',
                    'Akcionarsko društvo',
                    'Dio stranog društva (predstavništvo ili poslovna jedinica)',
                    'Udruženje (nvo, fondacije, sportske organizacije)',
                    'Ustanova (državne i privatne)',
                    'Druge organizacije (Političke partije, Vjerske zajednice, Komore, Sindikati)'
                ) NULL");
            } catch (\Exception $e) {
                // VARCHAR ili drugačiji tip kolone — podaci su već ažurirani
            }
        }

        if (Schema::hasTable('applications') && Schema::hasColumn('applications', 'registration_form')) {
            DB::table('applications')
                ->where('registration_form', self::OLD_VALUE)
                ->update(['registration_form' => self::NEW_VALUE]);
        }

        if (Schema::hasTable('business_plans') && Schema::hasColumn('business_plans', 'registration_form')) {
            DB::table('business_plans')
                ->where('registration_form', self::OLD_VALUE)
                ->update(['registration_form' => self::NEW_VALUE]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'user_type')) {
            DB::table('users')
                ->where('user_type', self::NEW_VALUE)
                ->update(['user_type' => self::OLD_VALUE]);

            try {
                DB::statement("ALTER TABLE `users` MODIFY COLUMN `user_type` ENUM(
                    'Fizičko lice',
                    'Preduzetnik',
                    'Ortačko društvo',
                    'Komanditno društvo',
                    'Društvo sa ograničenom odgovornošću',
                    'Akcionarsko društvo',
                    'Dio stranog društva (predstavništvo ili poslovna jedinica)',
                    'Udruženje (nvo, fondacije, sportske organizacije)',
                    'Ustanova (državne i privatne)',
                    'Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)'
                ) NULL");
            } catch (\Exception $e) {
                //
            }
        }

        if (Schema::hasTable('applications') && Schema::hasColumn('applications', 'registration_form')) {
            DB::table('applications')
                ->where('registration_form', self::NEW_VALUE)
                ->update(['registration_form' => self::OLD_VALUE]);
        }

        if (Schema::hasTable('business_plans') && Schema::hasColumn('business_plans', 'registration_form')) {
            DB::table('business_plans')
                ->where('registration_form', self::NEW_VALUE)
                ->update(['registration_form' => self::OLD_VALUE]);
        }
    }
};
