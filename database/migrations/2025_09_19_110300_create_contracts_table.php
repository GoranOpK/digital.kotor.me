<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    /**
     * Kreira tabelu za ugovore sa podržanim korisnicima/prijavama.
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade'); // Veza sa prijavom
            $table->string('status')->default('draft'); // Status ugovora (draft, potpisan, realizovan...)
            $table->string('contract_file')->nullable(); // Putanja do ugovora (pdf)
            $table->date('signed_at')->nullable(); // Datum potpisivanja
            $table->timestamps();
        });
    }

    /**
     * Briše tabelu.
     */
    public function down()
    {
        Schema::dropIfExists('contracts');
    }
}