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
            $table->enum('user_type', ['Fizičko lice', 'Registrovan privredni subjekt'])->nullable()->after('name');
            $table->string('first_name', 255)->nullable()->after('user_type');
            $table->string('last_name', 255)->nullable()->after('first_name');
            $table->string('phone', 50)->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('phone');
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

