<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NL-01 — kanonski Newsletter subscription data model (TS-011 v1.0.2).
 *
 * REPLACE: nova tabela `newsletter_subscriptions` (User-bound).
 * Legacy `newsletter_subscribers` (e-mail-only, testna) ostaje za weekly runtime
 * do kasnijeg NL paketa. Bez backfill-a testnih pretplatnika.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status', 32);
            $table->string('scope_mode', 32)->nullable();
            $table->boolean('include_without_organizer')->default(false);
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_token', 64)->nullable();
            $table->timestamps();

            $table->unique('user_id', 'nls_user_unique');
            $table->unique('unsubscribe_token', 'nls_unsub_token_unique');
            $table->index('status', 'nls_status_idx');

            $table->foreign('user_id', 'nls_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('newsletter_subscription_organizers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_subscription_id');
            $table->unsignedBigInteger('cultural_organizer_id');
            $table->timestamps();

            $table->unique(
                ['newsletter_subscription_id', 'cultural_organizer_id'],
                'nso_sub_org_unique'
            );

            $table->foreign('newsletter_subscription_id', 'nso_subscription_fk')
                ->references('id')->on('newsletter_subscriptions')->cascadeOnDelete();
            $table->foreign('cultural_organizer_id', 'nso_organizer_fk')
                ->references('id')->on('cultural_organizers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscription_organizers');
        Schema::dropIfExists('newsletter_subscriptions');
    }
};
