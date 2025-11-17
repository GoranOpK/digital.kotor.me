<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationsTable extends Migration
{
    public function up()
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('applicant_type')->comment('preduzetnica, privredno_drustvo');
            $table->string('business_stage')->comment('pocetak, razvoj');
            $table->text('business_plan');
            $table->string('status')->default('predato');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('applications');
    }
}