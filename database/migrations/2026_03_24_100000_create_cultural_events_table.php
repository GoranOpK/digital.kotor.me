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
        Schema::create('cultural_events', function (Blueprint $table) {
            $table->id();
            $table->string('naslov');
            $table->text('opis')->nullable();
            $table->date('datum_od');
            $table->date('datum_do')->nullable();
            $table->time('vrijeme')->nullable();
            $table->string('lokacija')->nullable();
            $table->enum('kategorija', [
                'Koncerti',
                'Predstave',
                'Izložbe',
                'Književne večeri',
                'Filmske projekcije',
                'Radionice',
                'Promocije publikacija',
                'Performansi',
                'Filmski festivali',
                'Likovne manifestacije',
                'Prezentacije',
                'Paneli o kulturi',
                'Manifestacije u organizaciji Mjesnih zajednica',
                'Manifestacije u organizaciji NVU',
            ]);
            $table->string('slika')->nullable();
            $table->enum('status', ['draft', 'published', 'archived', 'cancelled'])->default('draft');
            $table->boolean('featured')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'datum_od']);
            $table->index('featured');
            $table->index('kategorija');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cultural_events');
    }
};
