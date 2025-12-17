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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Naziv komisije
            $table->year('year'); // Godina mandata
            $table->date('start_date'); // Početak mandata
            $table->date('end_date'); // Kraj mandata (2 godine)
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('commission_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Ako je član iz sistema
            $table->string('name'); // Ime i prezime
            $table->string('position'); // Pozicija (predsjednik, član)
            $table->enum('member_type', ['opstina', 'udruzenje', 'zene_mreza']); // Tip člana
            $table->string('organization')->nullable(); // Organizacija (ako nije iz opštine)
            $table->text('confidentiality_declaration')->nullable(); // Izjava o tajnosti
            $table->text('conflict_of_interest_declaration')->nullable(); // Izjava o sukobu interesa
            $table->timestamp('declarations_signed_at')->nullable(); // Datum potpisivanja izjava
            $table->enum('status', ['active', 'resigned', 'dismissed'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_members');
        Schema::dropIfExists('commissions');
    }
};

