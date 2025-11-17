<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pokreće migraciju – dodaje polje 'role_id' u tabelu 'users'
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Dodaje se foreign key 'role_id' koji pokazuje na 'id' iz tabele 'roles'
            // Default vrijednost je 3 (npr. 1-admin, 2-komisija, 3-prijavitelj)
            $table->foreignId('role_id')->default(3)->constrained('roles');
        });
    }

    /**
     * Vraća migraciju unazad – uklanja polje 'role_id' iz tabele 'users'
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Prvo se uklanja foreign key constraint, pa zatim i samo polje 'role_id'
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};