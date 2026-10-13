<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KN-SYNC-015 — canonical MMSP Obrazac P1a / P1b (čl.16) and čl.14 document evidence.
 *
 * Required because the shared applications table has no column for šifra djelatnosti,
 * broj zaposlenih, datum registracije u CRPS / datum osnivanja or the odgovorno lice
 * contacts, and application_documents has no issue date (30-day rule) or conditional
 * variant (PDV / IOPPD / godišnji računi alternatives).
 *
 * Additive, canonical-only tables. Women/Youth never write them; no existing column changes.
 * PIB stays in applications.pib (beneficiary key); JMBG stays in the encrypted applicant_jmbg path.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('canonical_applicant_forms')) {
            Schema::create('canonical_applicant_forms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->unique()->constrained('applications')->cascadeOnDelete();
                $table->string('legal_form', 16);
                $table->string('subsidy_type', 64)->nullable();
                $table->string('person_name')->nullable();
                $table->string('company_name')->nullable();
                $table->string('address')->nullable();
                $table->string('activity_code', 16)->nullable();
                $table->unsignedInteger('employee_count')->nullable();
                $table->string('bank_account', 64)->nullable();
                $table->string('phone', 64)->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                $table->date('registration_date')->nullable();
                $table->timestamp('accuracy_confirmed_at')->nullable();
                $table->timestamp('frozen_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('canonical_document_evidence')) {
            Schema::create('canonical_document_evidence', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_document_id')->unique()->constrained('application_documents')->cascadeOnDelete();
                $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
                $table->string('document_type', 64);
                $table->string('variant', 64)->nullable();
                $table->date('issued_on')->nullable();
                $table->timestamp('frozen_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('canonical_document_evidence');
        Schema::dropIfExists('canonical_applicant_forms');
    }
};
