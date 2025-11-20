<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ukloni date_of_birth ako postoji
            if (Schema::hasColumn('users', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }

            // Dodaj activation_status ako ne postoji
            if (!Schema::hasColumn('users', 'activation_status')) {
                $table->enum('activation_status', ['active', 'deactivated'])->default('active')->after('id');
            }

            // Dodaj ili ažuriraj user_type enum sa novim vrednostima
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->enum('user_type', [
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
                ])->nullable()->after('activation_status');
            }
        });

        // Ažuriraj user_type enum ako već postoji (koristi raw SQL)
        if (Schema::hasColumn('users', 'user_type')) {
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
                // Ako ne može da se ažurira enum, ignoriši (možda već ima iste vrednosti)
            }
        }

        // Nastavi sa dodavanjem ostalih kolona
        Schema::table('users', function (Blueprint $table) {
            
            // Dodaj residential_status ako ne postoji
            if (!Schema::hasColumn('users', 'residential_status')) {
                $table->enum('residential_status', ['resident', 'non-resident', 'ex-non-resident'])->nullable()->after('user_type');
            }

            // Dodaj first_name i last_name ako ne postoje (mogu već postojati iz stare migracije)
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 255)->nullable()->after('residential_status');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 255)->nullable()->after('first_name');
            }

            // Dodaj JMB, PIB, passport_number (nove kolone)
            if (!Schema::hasColumn('users', 'jmb')) {
                $table->string('jmb', 13)->nullable()->unique()->after('last_name');
            }
            if (!Schema::hasColumn('users', 'pib')) {
                $table->string('pib', 9)->nullable()->unique()->after('jmb');
            }
            if (!Schema::hasColumn('users', 'passport_number')) {
                $table->string('passport_number', 50)->nullable()->unique()->after('pib');
            }

            // Dodaj phone ako ne postoji (može već postojati)
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email');
            }
        });

        // Dodaj indexe (ako već postoje, baza će ignorisati)
        try {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'activation_status')) {
                    $table->index('activation_status');
                }
            });
        } catch (\Exception $e) {
            // Index već postoji, ignoriši
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'user_type')) {
                    $table->index('user_type');
                }
            });
        } catch (\Exception $e) {
            // Index već postoji, ignoriši
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'residential_status')) {
                    $table->index('residential_status');
                }
            });
        } catch (\Exception $e) {
            // Index već postoji, ignoriši
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ukloni indexe
            $table->dropIndex(['activation_status']);
            $table->dropIndex(['user_type']);
            $table->dropIndex(['residential_status']);
            
            // Ukloni kolone
            $table->dropColumn([
                'activation_status',
                'user_type',
                'residential_status',
                'first_name',
                'last_name',
                'jmb',
                'pib',
                'passport_number',
                'phone'
            ]);
            
            // Vrati date_of_birth
            $table->date('date_of_birth')->nullable();
        });
    }
};

