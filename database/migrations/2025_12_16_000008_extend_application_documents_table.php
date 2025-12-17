<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('application_documents', 'document_type')) {
                $table->enum('document_type', [
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
                    'ostalo'
                ])->nullable()->after('type'); // Tip dokumenta prema Odluci
            }
            if (!Schema::hasColumn('application_documents', 'is_required')) {
                $table->boolean('is_required')->default(true)->after('document_type'); // Da li je dokument obavezan
            }
            if (!Schema::hasColumn('application_documents', 'user_document_id')) {
                $table->foreignId('user_document_id')->nullable()->constrained('user_documents')->onDelete('set null')->after('is_required'); // Veza sa bibliotekom dokumenata
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $columns = ['document_type', 'is_required', 'user_document_id'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('application_documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

