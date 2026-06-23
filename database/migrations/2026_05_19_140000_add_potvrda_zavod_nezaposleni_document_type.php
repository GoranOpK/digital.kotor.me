<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE application_documents MODIFY COLUMN document_type ENUM(
                'licna_karta',
                'crps_resenje',
                'pib_resenje',
                'pdv_resenje',
                'statut',
                'karton_potpisa',
                'potvrda_neosudjivanost',
                'uvjerenje_opstina_porezi',
                'uvjerenje_opstina_nepokretnost',
                'potvrda_upc_porezi',
                'ioppd_obrazac',
                'godisnji_racuni',
                'izvjestaj_realizacija',
                'finansijski_izvjestaj',
                'ostalo',
                'dokaz_ziro_racun',
                'predracuni_nabavka',
                'izvjestaj_registar_kase',
                'potvrda_zavod_nezaposleni'
            ) NULL");
        }
    }

    public function down(): void
    {
        // Preskačemo radi sigurnosti postojećih redova
    }
};
