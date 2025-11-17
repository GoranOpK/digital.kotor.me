<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenderPurchasesTable extends Migration
{
    public function up()
    {
        Schema::create('tender_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status')->default('pending');
            $table->string('download_link')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tender_purchases');
    }
}