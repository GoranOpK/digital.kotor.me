<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Kreira tabelu za obavještenja korisnicima portala.
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Korisnik kojem ide obavještenje
            $table->string('type')->default('info'); // Tip obavještenja (info, success, alert...)
            $table->text('message'); // Sadržaj obavještenja
            $table->boolean('is_read')->default(false); // Da li je pročitano
            $table->timestamps();
        });
    }

    /**
     * Briše tabelu.
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}