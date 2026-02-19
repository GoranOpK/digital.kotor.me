<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Dodaje nove tipove dokumenata za Preduzetnika koji započinje biznis:
     * dokaz_ziro_racun, predracuni_nabavka
     */
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
                'biznis_plan_usb',
                'izvjestaj_realizacija',
                'finansijski_izvjestaj',
                'ostalo',
                'dokaz_ziro_racun',
                'predracuni_nabavka'
            ) NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Opciono: uklanjanje novih vrijednosti zahtijeva ažuriranje postojećih redova; preskačemo radi sigurnosti
    }
};
