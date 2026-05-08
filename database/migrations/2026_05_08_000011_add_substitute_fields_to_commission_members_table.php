<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('commission_members', function (Blueprint $table) {
            if (!Schema::hasColumn('commission_members', 'is_substitute')) {
                $table->boolean('is_substitute')
                    ->default(false)
                    ->after('organization')
                    ->comment('Da li je član zamjenski');
            }

            if (!Schema::hasColumn('commission_members', 'replaces_member_number')) {
                $table->unsignedTinyInteger('replaces_member_number')
                    ->nullable()
                    ->after('is_substitute')
                    ->comment('Broj člana/predsjednika kojeg zamjenski član mijenja (1-5)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_members', function (Blueprint $table) {
            if (Schema::hasColumn('commission_members', 'replaces_member_number')) {
                $table->dropColumn('replaces_member_number');
            }

            if (Schema::hasColumn('commission_members', 'is_substitute')) {
                $table->dropColumn('is_substitute');
            }
        });
    }
};

