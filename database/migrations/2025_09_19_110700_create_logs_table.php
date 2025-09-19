<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    /**
     * Kreira tabelu za evidenciju aktivnosti korisnika i sistema.
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Korisnik (ako postoji)
            $table->string('action'); // Akcija (prijava, bodovanje, upload, odobrenje...)
            $table->text('details')->nullable(); // Detalji radnje
            $table->timestamps();
        });
    }

    /**
     * Briše tabelu.
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
}