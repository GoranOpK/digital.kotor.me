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
        Schema::table('users', function (Blueprint $table) {
            // Dodaj samo ako kolone ne postoje (zaštita od ponovnog pokretanja)
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->enum('user_type', ['Fizičko lice', 'Registrovan privredni subjekt'])->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 255)->nullable()->after('user_type');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 255)->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_type', 'first_name', 'last_name', 'phone', 'date_of_birth']);
        });
    }
};

