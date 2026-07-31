<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('short_description')->nullable();
            $table->boolean('visible_in_active_panel')->default(true);
            $table->string('source_type', 64);
            $table->unsignedBigInteger('source_id');
            $table->string('content_delivery', 64);
            $table->timestamp('published_at');
            $table->timestamps();

            $table->index(
                ['visible_in_active_panel', 'published_at'],
                'notices_visible_published_index'
            );
            $table->index(
                ['source_type', 'source_id'],
                'notices_source_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
