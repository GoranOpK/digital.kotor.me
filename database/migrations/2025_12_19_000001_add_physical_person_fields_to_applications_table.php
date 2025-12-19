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
        Schema::table('applications', function (Blueprint $table) {
            // Dodaj polja za fizičko lice BEZ registrovane djelatnosti
            // VAŽNO: Ova polja se koriste samo za 'fizicko_lice' tip (fizičko lice bez registrovane djelatnosti)
            //        'preduzetnica' tip (fizičko lice SA registrovanom djelatnošću) ne koristi ova polja
            if (!Schema::hasColumn('applications', 'physical_person_name')) {
                $table->string('physical_person_name')->nullable()->after('company_seat');
            }
            if (!Schema::hasColumn('applications', 'physical_person_jmbg')) {
                $table->string('physical_person_jmbg', 13)->nullable()->after('physical_person_name');
            }
            if (!Schema::hasColumn('applications', 'physical_person_phone')) {
                $table->string('physical_person_phone', 50)->nullable()->after('physical_person_jmbg');
            }
            if (!Schema::hasColumn('applications', 'physical_person_email')) {
                $table->string('physical_person_email')->nullable()->after('physical_person_phone');
            }
        });

        // Ažuriraj enum za applicant_type da uključi 'fizicko_lice' i 'ostalo'
        // VAŽNO: 'fizicko_lice' = Fizičko lice BEZ registrovane djelatnosti
        //        'preduzetnica' = Fizičko lice SA registrovanom djelatnošću (preduzetnik)
        // Prvo proveri da li je kolona enum tipa
        $columnType = DB::select("SHOW COLUMNS FROM applications WHERE Field = 'applicant_type'");
        
        if (!empty($columnType)) {
            $columnInfo = (array) $columnType[0];
            
            // Ako je enum, proširi ga
            if (strpos($columnInfo['Type'], 'enum') !== false) {
                // MySQL/MariaDB način za promenu enum-a
                DB::statement("ALTER TABLE applications MODIFY COLUMN applicant_type ENUM('preduzetnica', 'doo', 'fizicko_lice', 'ostalo') NULL");
            } else {
                // Ako nije enum, samo proveri da li postoji constraint ili promeni u string
                // Pošto je već string u nekim migracijama, možda je već string
                // U tom slučaju ne treba ništa raditi jer string prihvata bilo koje vrednosti
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $columns = [
                'physical_person_name',
                'physical_person_jmbg',
                'physical_person_phone',
                'physical_person_email'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('applications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Vrati enum na originalnu vrednost (samo preduzetnica i doo)
        $columnType = DB::select("SHOW COLUMNS FROM applications WHERE Field = 'applicant_type'");
        
        if (!empty($columnType)) {
            $columnInfo = (array) $columnType[0];
            
            if (strpos($columnInfo['Type'], 'enum') !== false) {
                DB::statement("ALTER TABLE applications MODIFY COLUMN applicant_type ENUM('preduzetnica', 'doo') NULL");
            }
        }
    }
};
