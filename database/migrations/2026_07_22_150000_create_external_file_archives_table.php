<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_file_archives', function (Blueprint $table) {
            $table->id();
            $table->string('source_table', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('source_column', 100);
            $table->string('context_type', 100);
            $table->string('archive_provider', 50)->default('mega');
            $table->string('generated_file_name', 255);
            $table->string('mega_node_id', 255)->nullable();
            $table->string('mega_path', 500)->nullable();
            $table->string('original_local_path', 500);
            $table->timestamp('local_deleted_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->string('status', 50)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(
                ['source_table', 'source_id', 'source_column', 'archive_provider'],
                'efa_source_provider_unique'
            );
            $table->unique('generated_file_name');
            $table->index('status');
            $table->index('mega_node_id');
            $table->index(['source_table', 'source_id'], 'efa_source_index');
            $table->index(['archive_provider', 'status'], 'efa_provider_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_file_archives');
    }
};
