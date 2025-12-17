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
        Schema::create('evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->foreignId('commission_member_id')->constrained('commission_members')->onDelete('cascade');
            
            // 10 pozitivnih kriterijuma (1-5 poena svaki)
            $table->integer('criterion_1')->nullable()->comment('Obrazac biznis plana detaljno popunjen');
            $table->integer('criterion_2')->nullable()->comment('Biznis ideja je inovativna');
            $table->integer('criterion_3')->nullable()->comment('Jasno identifikovani potencijalni kupci');
            $table->integer('criterion_4')->nullable()->comment('Omogućava samozapošljavanje/zapošljavanje');
            $table->integer('criterion_5')->nullable()->comment('Prepoznata konkurencija');
            $table->integer('criterion_6')->nullable()->comment('Jasno navedeni potrebni resursi');
            $table->integer('criterion_7')->nullable()->comment('Finansijski održiva');
            $table->integer('criterion_8')->nullable()->comment('Podaci o preduzetnici');
            $table->integer('criterion_9')->nullable()->comment('Razvijena matrica rizika');
            $table->integer('criterion_10')->nullable()->comment('Usmeno obrazloženje');
            
            // Prosječne ocjene (izračunate)
            $table->decimal('average_score', 5, 2)->nullable(); // Prosječna ocjena svih članova
            $table->decimal('final_score', 5, 2)->nullable(); // Konačna ocjena (zbir prosjeka)
            
            // Dodatne napomene
            $table->text('notes')->nullable(); // Ostale napomene
            
            $table->timestamps();
            
            // Jedinstvena kombinacija - jedan član komisije može ocjeniti jednu prijavu jednom
            $table->unique(['application_id', 'commission_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_scores');
    }
};

