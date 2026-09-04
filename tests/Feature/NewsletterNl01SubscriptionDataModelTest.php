<?php

namespace Tests\Feature;

use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterSubscription;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * NL-01 — kanonski Newsletter subscription data model (TS-011 v1.0.2).
 */
class NewsletterNl01SubscriptionDataModelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
    }

    public function test_user_can_exist_without_newsletter_subscription(): void
    {
        $this->assertDatabaseCount('newsletter_subscriptions', 0);
        $this->assertNull($this->user->newsletterSubscription);
        $this->assertFalse($this->user->newsletterSubscription()->exists());
    }

    public function test_creating_user_does_not_auto_create_subscription(): void
    {
        $before = NewsletterSubscription::query()->count();

        User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->assertSame($before, NewsletterSubscription::query()->count());
        $this->assertSame(0, NewsletterSubscription::query()->count());
    }

    public function test_user_has_at_most_one_subscription_via_unique_constraint(): void
    {
        $this->makeActiveSubscription($this->user);

        $this->expectException(QueryException::class);

        NewsletterSubscription::query()->create([
            'user_id' => $this->user->id,
            'status' => NewsletterSubscription::STATUS_ACTIVE,
            'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
            'subscribed_at' => now(),
        ]);
    }

    public function test_subscription_belongs_to_user_and_user_has_one_subscription(): void
    {
        $subscription = $this->makeActiveSubscription($this->user);

        $this->assertTrue($subscription->user->is($this->user));
        $this->assertTrue($this->user->fresh()->newsletterSubscription->is($subscription));
    }

    public function test_active_and_unsubscribed_status_and_timestamps_persist(): void
    {
        $subscribedAt = now()->subDay();
        $subscription = NewsletterSubscription::query()->create([
            'user_id' => $this->user->id,
            'status' => NewsletterSubscription::STATUS_ACTIVE,
            'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
            'subscribed_at' => $subscribedAt,
        ]);

        $fresh = $subscription->fresh();
        $this->assertTrue($fresh->isActive());
        $this->assertFalse($fresh->isUnsubscribed());
        $this->assertSame($subscribedAt->format('Y-m-d H:i:s'), $fresh->subscribed_at->format('Y-m-d H:i:s'));
        $this->assertNull($fresh->unsubscribed_at);

        $unsubscribedAt = now();
        $fresh->applyUnsubscribeState();

        $after = $fresh->fresh();
        $this->assertTrue($after->isUnsubscribed());
        $this->assertFalse($after->isActive());
        $this->assertNotNull($after->unsubscribed_at);
        $this->assertTrue($after->unsubscribed_at->equalTo($unsubscribedAt) || $after->unsubscribed_at->diffInSeconds($unsubscribedAt) < 2);
        $this->assertNull($after->scope_mode);
        $this->assertFalse($after->include_without_organizer);
    }

    public function test_scope_mode_all_events_persists_without_organizer_pivot(): void
    {
        $organizer = $this->makeOrganizer('Org A');
        $subscription = $this->makeActiveSubscription($this->user);
        $subscription->applyAllEventsScope();

        $fresh = $subscription->fresh();
        $this->assertTrue($fresh->usesAllEventsScope());
        $this->assertFalse($fresh->usesSelectedOrganizerScope());
        $this->assertTrue($fresh->includesEventsWithoutOrganizer());
        $this->assertFalse($fresh->include_without_organizer);
        $this->assertCount(0, $fresh->organizers);
        $this->assertDatabaseCount('newsletter_subscription_organizers', 0);
        $this->assertTrue(CulturalOrganizer::query()->whereKey($organizer->id)->exists());
    }

    public function test_scope_mode_selected_organizers_and_include_without_organizer_persist(): void
    {
        $one = $this->makeOrganizer('Jedan');
        $two = $this->makeOrganizer('Dva');

        $subscription = $this->makeActiveSubscription($this->user, NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS);
        $subscription->include_without_organizer = true;
        $subscription->save();
        $subscription->organizers()->attach([$one->id, $two->id]);

        $fresh = $subscription->fresh();
        $this->assertTrue($fresh->usesSelectedOrganizerScope());
        $this->assertTrue($fresh->include_without_organizer);
        $this->assertTrue($fresh->includesEventsWithoutOrganizer());
        $this->assertEqualsCanonicalizing([$one->id, $two->id], $fresh->organizers->pluck('id')->all());
    }

    public function test_selected_mode_can_have_only_without_organizer_and_no_pivot(): void
    {
        $subscription = $this->makeActiveSubscription($this->user, NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS);
        $subscription->include_without_organizer = true;
        $subscription->save();

        $this->assertCount(0, $subscription->fresh()->organizers);
        $this->assertTrue($subscription->fresh()->includesEventsWithoutOrganizer());
    }

    public function test_duplicate_pivot_pair_is_forbidden(): void
    {
        $organizer = $this->makeOrganizer('Duplikat');
        $subscription = $this->makeActiveSubscription($this->user, NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS);
        $subscription->organizers()->attach($organizer->id);

        $this->expectException(QueryException::class);
        $subscription->organizers()->attach($organizer->id);
    }

    public function test_pivot_fk_rejects_unknown_organizer(): void
    {
        $subscription = $this->makeActiveSubscription($this->user, NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS);

        $this->expectException(QueryException::class);
        DB::table('newsletter_subscription_organizers')->insert([
            'newsletter_subscription_id' => $subscription->id,
            'cultural_organizer_id' => 9_999_999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_deleting_subscription_cleans_pivot(): void
    {
        $organizer = $this->makeOrganizer('Pivot cleanup');
        $subscription = $this->makeActiveSubscription($this->user, NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS);
        $subscription->organizers()->attach($organizer->id);

        $subscription->delete();

        $this->assertDatabaseCount('newsletter_subscription_organizers', 0);
        $this->assertDatabaseHas('cultural_organizers', ['id' => $organizer->id]);
    }

    public function test_deleting_user_removes_subscription_and_pivot(): void
    {
        $owner = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $organizer = $this->makeOrganizer('User cascade');
        $subscription = $this->makeActiveSubscription($owner, NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS);
        $subscription->organizers()->attach($organizer->id);

        $owner->delete();

        $this->assertDatabaseMissing('newsletter_subscriptions', ['id' => $subscription->id]);
        $this->assertDatabaseCount('newsletter_subscription_organizers', 0);
        $this->assertDatabaseHas('cultural_organizers', ['id' => $organizer->id]);
    }

    public function test_organizer_deactivation_and_reactivation_preserve_pivot(): void
    {
        $organizer = $this->makeOrganizer('Deaktivacija');
        $subscription = $this->makeActiveSubscription($this->user, NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS);
        $subscription->organizers()->attach($organizer->id);

        $organizer->status = CulturalOrganizer::STATUS_DEACTIVATED;
        $organizer->save();

        $this->assertDatabaseHas('newsletter_subscription_organizers', [
            'newsletter_subscription_id' => $subscription->id,
            'cultural_organizer_id' => $organizer->id,
        ]);
        $this->assertTrue($subscription->fresh()->organizers()->whereKey($organizer->id)->exists());

        $organizer->status = CulturalOrganizer::STATUS_ACTIVE;
        $organizer->save();

        $this->assertDatabaseHas('newsletter_subscription_organizers', [
            'newsletter_subscription_id' => $subscription->id,
            'cultural_organizer_id' => $organizer->id,
        ]);
    }

    public function test_physical_organizer_delete_does_not_delete_subscription(): void
    {
        $organizer = $this->makeOrganizer('Brisanje org');
        $subscription = $this->makeActiveSubscription($this->user, NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS);
        $subscription->organizers()->attach($organizer->id);

        $organizer->delete();

        $this->assertDatabaseHas('newsletter_subscriptions', ['id' => $subscription->id]);
        $this->assertDatabaseCount('newsletter_subscription_organizers', 0);
    }

    public function test_manual_organizer_text_is_not_a_subscription_relation(): void
    {
        $this->assertFalse(Schema::hasColumn('newsletter_subscriptions', 'organizer_manual_name'));
        $this->assertFalse(Schema::hasColumn('newsletter_subscription_organizers', 'organizer_manual_name'));

        $entry = CulturalEventEntry::query()->create([
            'naslov' => 'Bez kanonskog org',
            'status' => CulturalEventEntry::STATUS_DRAFT,
            'created_by' => $this->user->id,
            'organizer_id' => null,
            'organizer_manual_name' => 'Ručni naziv',
        ]);

        $subscription = $this->makeActiveSubscription($this->user, NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS);
        $subscription->include_without_organizer = true;
        $subscription->save();

        $this->assertNull($entry->organizer_id);
        $this->assertSame('Ručni naziv', $entry->organizer_manual_name);
        $this->assertCount(0, $subscription->fresh()->organizers);
        $this->assertTrue($subscription->fresh()->includesEventsWithoutOrganizer());
    }

    public function test_schema_has_no_manifestation_subscription_relation(): void
    {
        $this->assertFalse(Schema::hasColumn('newsletter_subscriptions', 'manifestation_id'));
        $this->assertFalse(Schema::hasTable('newsletter_subscription_manifestations'));
        $this->assertFalse(method_exists(NewsletterSubscription::class, 'manifestations'));
        $this->assertFalse(method_exists(NewsletterSubscription::class, 'manifestation'));
        $this->assertTrue(class_exists(CulturalManifestation::class));
    }

    public function test_canonical_schema_has_no_email_ssot_and_keeps_unsubscribe_token(): void
    {
        $this->assertFalse(Schema::hasColumn('newsletter_subscriptions', 'email'));
        $this->assertFalse(Schema::hasColumn('newsletter_subscriptions', 'confirmation_sent_at'));
        $this->assertFalse(Schema::hasColumn('newsletter_subscriptions', 'first_activated_at'));
        $this->assertTrue(Schema::hasColumn('newsletter_subscriptions', 'unsubscribe_token'));
        $this->assertTrue(Schema::hasColumn('newsletter_subscriptions', 'user_id'));
        $this->assertTrue(Schema::hasColumn('newsletter_subscriptions', 'status'));
        $this->assertTrue(Schema::hasColumn('newsletter_subscriptions', 'scope_mode'));
        $this->assertTrue(Schema::hasColumn('newsletter_subscriptions', 'include_without_organizer'));
        $this->assertTrue(Schema::hasColumn('newsletter_subscriptions', 'subscribed_at'));
        $this->assertTrue(Schema::hasColumn('newsletter_subscriptions', 'unsubscribed_at'));

        $token = str_repeat('a', 64);
        $subscription = $this->makeActiveSubscription($this->user);
        $subscription->unsubscribe_token = $token;
        $subscription->save();

        $this->assertSame($token, $subscription->fresh()->unsubscribe_token);
    }

    public function test_legacy_subscriber_rows_are_not_migrated_into_canonical_subscriptions(): void
    {
        NewsletterSubscriber::query()->create([
            'email' => $this->user->email,
            'is_subscribed' => true,
            'subscribed_at' => now(),
            'unsubscribe_token' => str_repeat('b', 64),
        ]);

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->assertDatabaseCount('newsletter_subscriptions', 0);
        $this->assertNull($this->user->fresh()->newsletterSubscription);
    }

    public function test_unsubscribe_clears_preferences_and_keeps_row_for_reactivation(): void
    {
        $organizer = $this->makeOrganizer('Odjava');
        $subscription = $this->makeActiveSubscription($this->user, NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS);
        $subscription->include_without_organizer = true;
        $subscription->save();
        $subscription->organizers()->attach($organizer->id);

        $subscription->applyUnsubscribeState();
        $id = $subscription->id;

        $unsubscribed = $subscription->fresh();
        $this->assertDatabaseHas('newsletter_subscriptions', ['id' => $id]);
        $this->assertTrue($unsubscribed->isUnsubscribed());
        $this->assertCount(0, $unsubscribed->organizers);
        $this->assertFalse($unsubscribed->include_without_organizer);

        $unsubscribed->applyReactivationState();
        $reactivated = $unsubscribed->fresh();
        $this->assertSame($id, $reactivated->id);
        $this->assertTrue($reactivated->isActive());
        $this->assertNull($reactivated->unsubscribed_at);
        $this->assertNull($reactivated->scope_mode);
        $this->assertCount(0, $reactivated->organizers);
    }

    public function test_user_deactivation_does_not_delete_subscription_or_preferences(): void
    {
        $organizer = $this->makeOrganizer('User deact');
        $subscription = $this->makeActiveSubscription($this->user, NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS);
        $subscription->organizers()->attach($organizer->id);

        $this->user->activation_status = 'deactivated';
        $this->user->save();

        $this->assertDatabaseHas('newsletter_subscriptions', ['id' => $subscription->id]);
        $this->assertDatabaseHas('newsletter_subscription_organizers', [
            'newsletter_subscription_id' => $subscription->id,
            'cultural_organizer_id' => $organizer->id,
        ]);
    }

    public function test_migration_rollback_drops_canonical_tables_and_keeps_legacy(): void
    {
        $this->assertTrue(Schema::hasTable('newsletter_subscriptions'));
        $this->assertTrue(Schema::hasTable('newsletter_subscription_organizers'));
        $this->assertTrue(Schema::hasTable('newsletter_subscription_source_coverages'));
        $this->assertTrue(Schema::hasTable('newsletter_pending_priority_changes'));
        $this->assertTrue(Schema::hasTable('newsletter_delivery_ledger'));
        $this->assertTrue(Schema::hasTable('newsletter_subscribers'));
        $this->assertTrue(Schema::hasColumn('cultural_event_entries', 'first_published_at'));

        if (Schema::hasTable('cultural_activity_records')) {
            while (Schema::hasTable('cultural_activity_records')) {
                $this->artisan('migrate:rollback', ['--step' => 1])->assertSuccessful();
            }
            $this->assertFalse(Schema::hasTable('cultural_activity_records'));
        }

        $this->artisan('migrate:rollback', ['--step' => 1])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('newsletter_pending_priority_changes'));
        $this->assertTrue(Schema::hasTable('newsletter_delivery_ledger'));
        $this->assertTrue(Schema::hasTable('newsletter_subscription_source_coverages'));
        $this->assertTrue(Schema::hasColumn('cultural_event_entries', 'first_published_at'));

        $this->artisan('migrate:rollback', ['--step' => 1])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('newsletter_delivery_ledger'));
        $this->assertTrue(Schema::hasTable('newsletter_subscription_source_coverages'));
        $this->assertTrue(Schema::hasColumn('cultural_event_entries', 'first_published_at'));
        $this->assertTrue(Schema::hasTable('newsletter_subscriptions'));
        $this->assertTrue(Schema::hasTable('newsletter_subscribers'));

        $this->artisan('migrate:rollback', ['--step' => 1])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('newsletter_subscription_source_coverages'));
        $this->assertFalse(Schema::hasColumn('cultural_event_entries', 'first_published_at'));
        $this->assertTrue(Schema::hasTable('newsletter_subscriptions'));
        $this->assertTrue(Schema::hasTable('newsletter_subscribers'));

        $this->artisan('migrate:rollback', ['--step' => 1])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('newsletter_subscriptions'));
        $this->assertFalse(Schema::hasTable('newsletter_subscription_organizers'));
        $this->assertTrue(Schema::hasTable('newsletter_subscribers'));

        $this->artisan('migrate')->assertSuccessful();

        $this->assertTrue(Schema::hasTable('newsletter_subscriptions'));
        $this->assertTrue(Schema::hasTable('newsletter_subscription_organizers'));
        $this->assertTrue(Schema::hasTable('newsletter_subscription_source_coverages'));
        $this->assertTrue(Schema::hasTable('newsletter_pending_priority_changes'));
        $this->assertTrue(Schema::hasTable('newsletter_delivery_ledger'));
        $this->assertTrue(Schema::hasColumn('cultural_event_entries', 'first_published_at'));
    }

    private function makeActiveSubscription(
        User $user,
        string $scopeMode = NewsletterSubscription::SCOPE_ALL_EVENTS
    ): NewsletterSubscription {
        return NewsletterSubscription::query()->create([
            'user_id' => $user->id,
            'status' => NewsletterSubscription::STATUS_ACTIVE,
            'scope_mode' => $scopeMode,
            'include_without_organizer' => false,
            'subscribed_at' => now(),
        ]);
    }

    private function makeOrganizer(string $naziv): CulturalOrganizer
    {
        $request = CulturalOrganizerCreationRequest::query()->create([
            'submitter_user_id' => $this->user->id,
            'proposed_moderator_user_id' => $this->user->id,
            'proposed_moderator_name' => $this->user->name,
            'proposed_moderator_email' => $this->user->email,
            'proposed_naziv' => $naziv,
            'proposed_moderator_is_submitter' => true,
            'status' => CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => User::factory()->create([
                'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
                'activation_status' => 'active',
            ])->id,
            'decision_at' => now(),
        ]);

        return CulturalOrganizer::query()->create([
            'naziv' => $naziv,
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $request->id,
        ]);
    }
}
