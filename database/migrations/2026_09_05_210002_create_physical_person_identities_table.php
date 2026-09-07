<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_person_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_identity_id')
                ->unique()
                ->constrained('platform_identities')
                ->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('residential_status', 16);
            $table->string('id_document_type', 16)->nullable();
            $table->char('jmb', 13)->nullable();
            $table->string('passport_number', 50)->nullable();
            $table->string('residence_country_code', 8)->nullable();
            $table->boolean('is_entrepreneur')->default(false);
            $table->string('entrepreneur_business_name')->nullable();
            $table->char('pib', 8)->nullable();
            $table->char('crps_number', 8)->nullable();
            $table->string('street_and_number');
            $table->string('city');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_person_identities');
    }
};
