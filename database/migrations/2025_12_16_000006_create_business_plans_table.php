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
        Schema::create('business_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            
            // I. OSNOVNI PODACI
            $table->string('business_idea_name')->nullable(); // Naziv biznis ideje
            $table->text('applicant_data')->nullable(); // Podaci o podnosiocu (JSON)
            $table->text('registered_activity_data')->nullable(); // Podaci o registrovanoj djelatnosti (JSON)
            $table->text('summary')->nullable(); // Rezime
            
            // II. MARKETING
            $table->text('product_service')->nullable(); // Proizvod/Usluga
            $table->text('location')->nullable(); // Lokacija
            $table->text('pricing')->nullable(); // Cijena
            $table->text('promotion')->nullable(); // Promocija
            $table->text('people_marketing')->nullable(); // Ljudi
            
            // III. POSLOVANJE
            $table->text('business_analysis')->nullable(); // Analiza dosadašnjeg poslovanja
            $table->text('supply_market')->nullable(); // Nabavno tržište
            
            // IV. FINANSIJE
            $table->text('required_funds')->nullable(); // Potrebna sredstva i izvori finansiranja
            $table->text('revenue_expense_projection')->nullable(); // Projekcija prihoda i rashoda
            
            // V. LJUDI
            $table->text('entrepreneur_data')->nullable(); // Podaci o preduzetnici
            $table->text('job_schedule')->nullable(); // Raspored poslova
            
            // VI. RIZICI
            $table->text('risk_matrix')->nullable(); // Matrica upravljanja rizicima
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_plans');
    }
};

