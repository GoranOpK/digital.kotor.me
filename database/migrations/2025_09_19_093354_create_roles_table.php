<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pokreće migraciju – kreira tabelu 'roles' u bazi
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); // Primarni ključ, autoinkrement (npr. 1, 2, 3...)
            $table->string('name')->unique(); // Naziv uloge (npr. 'admin', 'komisija', 'prijavitelj'), mora biti jedinstven
            $table->string('display_name')->nullable(); // Lijepo ime za prikaz (može biti prazno, npr. 'Administrator')
            $table->timestamps(); // Polja 'created_at' i 'updated_at' – Laravel automatski popunjava vrijeme kreiranja i izmjene
        });
    }

    /**
     * Vraća migraciju unazad – briše tabelu 'roles' iz baze
     */
    public function down(): void
    {
        Schema::dropIfExists('roles'); // Briše tabelu ako postoji
    }
};