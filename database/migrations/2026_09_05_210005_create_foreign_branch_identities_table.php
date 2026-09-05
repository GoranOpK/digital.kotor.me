<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foreign_branch_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_identity_id')
                ->unique()
                ->constrained('platform_identities')
                ->cascadeOnDelete();
            $table->string('foreign_company_name');
            $table->string('branch_name_in_montenegro');
            $table->char('pib', 8)->nullable();
            $table->char('crps_number', 8)->nullable();
            $table->string('street_and_number');
            $table->string('city');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foreign_branch_identities');
    }
};
