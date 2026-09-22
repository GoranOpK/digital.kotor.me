<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('youth_equal_score_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('competition_id');
            $table->string('group_key', 191);
            $table->decimal('full_score', 16, 10);
            $table->unsignedInteger('ranking_position');
            $table->string('applied_rule', 64);
            $table->timestamps();

            $table->foreign('competition_id', 'yesg_competition_fk')
                ->references('id')
                ->on('competitions')
                ->cascadeOnDelete();
            $table->unique(['competition_id', 'group_key'], 'yesg_comp_key_uq');
        });

        Schema::create('youth_equal_score_rounds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedInteger('round_no');
            $table->text('justification');
            $table->decimal('budget_before', 12, 2);
            $table->decimal('proposal_total', 12, 2);
            $table->timestamp('locked_at')->nullable();
            $table->string('outcome', 16)->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('created_by_member_id');
            $table->string('created_by_name');
            $table->unsignedBigInteger('locked_by_user_id')->nullable();
            $table->unsignedBigInteger('locked_by_member_id')->nullable();
            $table->string('locked_by_name')->nullable();
            $table->timestamps();

            $table->foreign('group_id', 'yesr_group_fk')
                ->references('id')
                ->on('youth_equal_score_groups')
                ->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'yesr_created_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('created_by_member_id', 'yesr_created_member_fk')
                ->references('id')
                ->on('commission_members')
                ->restrictOnDelete();
            $table->foreign('locked_by_user_id', 'yesr_locked_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('locked_by_member_id', 'yesr_locked_member_fk')
                ->references('id')
                ->on('commission_members')
                ->restrictOnDelete();
            $table->unique(['group_id', 'round_no'], 'yesr_group_round_uq');
        });

        Schema::create('youth_equal_score_round_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->unsignedBigInteger('application_id');
            $table->string('business_stage', 64);
            $table->decimal('requested_amount', 12, 2)->nullable();
            $table->decimal('draft_amount', 12, 2);
            $table->unsignedTinyInteger('applied_cap_percent')->nullable();
            $table->boolean('selected');
            $table->timestamps();

            $table->foreign('round_id', 'yesra_round_fk')
                ->references('id')
                ->on('youth_equal_score_rounds')
                ->cascadeOnDelete();
            $table->foreign('application_id', 'yesra_application_fk')
                ->references('id')
                ->on('applications')
                ->restrictOnDelete();
            $table->unique(['round_id', 'application_id'], 'yesra_round_app_uq');
        });

        Schema::create('youth_equal_score_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->unsignedTinyInteger('canonical_seat_no');
            $table->unsignedBigInteger('commission_member_id');
            $table->unsignedBigInteger('user_id');
            $table->string('member_name');
            $table->string('vote_value', 16);
            $table->unsignedBigInteger('recorded_by_user_id');
            $table->timestamp('voted_at');
            $table->timestamps();

            $table->foreign('round_id', 'yesv_round_fk')
                ->references('id')
                ->on('youth_equal_score_rounds')
                ->cascadeOnDelete();
            $table->foreign('commission_member_id', 'yesv_member_fk')
                ->references('id')
                ->on('commission_members')
                ->restrictOnDelete();
            $table->foreign('user_id', 'yesv_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('recorded_by_user_id', 'yesv_recorded_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->unique(['round_id', 'canonical_seat_no'], 'yesv_round_seat_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youth_equal_score_votes');
        Schema::dropIfExists('youth_equal_score_round_applications');
        Schema::dropIfExists('youth_equal_score_rounds');
        Schema::dropIfExists('youth_equal_score_groups');
    }
};
