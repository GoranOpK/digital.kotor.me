<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foreign_branch_representatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foreign_branch_identity_id')
                ->unique()
                ->constrained('foreign_branch_identities', 'id', 'fbr_foreign_branch_identity_fk')
                ->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('id_document_type', 16)->nullable();
            $table->char('jmb', 13)->nullable();
            $table->string('passport_number', 50)->nullable();
            $table->string('passport_issuing_country_code', 8)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foreign_branch_representatives');
    }
};
