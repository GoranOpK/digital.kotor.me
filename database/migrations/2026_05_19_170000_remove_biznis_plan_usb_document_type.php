<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DOCUMENT_TYPES_WITHOUT_USB = [
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
        'potvrda_zavod_nezaposleni',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('application_documents')
            ->where('document_type', 'biznis_plan_usb')
            ->update(['document_type' => 'ostalo']);

        $enum = "'" . implode("','", self::DOCUMENT_TYPES_WITHOUT_USB) . "'";
        DB::statement("ALTER TABLE application_documents MODIFY COLUMN document_type ENUM({$enum}) NULL");
    }

    public function down(): void
    {
        // Bez vraćanja biznis_plan_usb radi sigurnosti postojećih redova
    }
};
