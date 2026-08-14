<?php

namespace Tests\Feature;

use App\Mail\CulturalCalendarNewsletterWelcomeMail;
use App\Models\CulturalOrganizer;
use App\Models\CulturalOrganizerCreationRequest;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterSubscription;
use App\Models\Role;
use App\Models\User;
use App\Services\Newsletter\NewsletterSubscriptionManager;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * NL-02 — User Newsletter subscribe / preferences / unsubscribe UX.
 */
class NewsletterNl02SubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
    }

    public function test_guest_cannot_access_newsletter_settings(): void
    {
        $this->get(route('newsletter.settings'))->assertRedirect(route('login'));
        $this->post(route('newsletter.subscribe'), [
            'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
        ])->assertRedirect(route('login'));
    }

    public function test_unverified_user_cannot_first_subscribe(): void
    {
        $unverified = User::factory()->unverified()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->actingAs($unverified)
            ->get(route('newsletter.settings'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($unverified)
            ->post(route('newsletter.subscribe'), [
                'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
            ])
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('newsletter_subscriptions', 0);
        Mail::assertNothingSent();
    }

    public function test_inactive_user_cannot_first_subscribe(): void
    {
        $this->user->forceFill(['activation_status' => 'deactivated'])->save();

        $this->actingAs($this->user)
            ->get(route('newsletter.settings'))
            ->assertForbidden();

        $this->actingAs($this->user)
            ->post(route('newsletter.subscribe'), [
                'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('newsletter_subscriptions', 0);
    }

    public function test_verified_active_user_can_view_settings_and_get_does_not_create_subscription(): void
    {
        $this->actingAs($this->user)
            ->get(route('newsletter.settings'))
            ->assertOk()
            ->assertSee('Newsletter', false)
            ->assertSee('Niste pretplaćeni', false)
            ->assertSee('Odaberite sadržaj koji želite da pratite.', false)
            ->assertSee('Svi događaji', false)
            ->assertSee('Odabrani organizatori', false)
            ->assertSee('Bez organizatora', false)
            ->assertSee('Pretplati se', false)
            ->assertDontSee('Sačuvaj izmjene', false)
            ->assertDontSee('Odjavi se', false)
            ->assertDontSee('Prati Manifestaciju', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="user_id"', false);

        $this->actingAs($this->user)->get(route('newsletter.settings'));

        $this->assertDatabaseCount('newsletter_subscriptions', 0);
        $this->assertNull($this->user->fresh()->newsletterSubscription);
    }

    public function test_first_view_has_no_default_scope_selected(): void
    {
        $html = $this->actingAs($this->user)->get(route('newsletter.settings'))->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/id="scope_all_events"[^>]*checked/i',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/id="scope_selected_organizers"[^>]*checked/i',
            $html
        );
    }

    public function test_first_subscribe_all_events_works(): void
    {
        $organizer = $this->makeOrganizer('Org A');

        $response = $this->actingAs($this->user)->post(route('newsletter.subscribe'), [
            'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
            'organizer_ids' => [$organizer->id],
            'include_without_organizer' => '1',
        ]);

        $response->assertRedirect(route('newsletter.settings'));
        $response->assertSessionHas('success', NewsletterSubscriptionManager::MESSAGE_SUBSCRIBED);

        $this->assertDatabaseCount('newsletter_subscriptions', 1);
        $subscription = $this->user->fresh()->newsletterSubscription;
        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->usesAllEventsScope());
        $this->assertFalse($subscription->include_without_organizer);
        $this->assertCount(0, $subscription->organizers);
        $this->assertNotNull($subscription->unsubscribe_token);
        $this->assertNull($subscription->unsubscribed_at);
        Mail::assertNothingSent();
        Mail::assertNotSent(CulturalCalendarNewsletterWelcomeMail::class);
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_first_subscribe_selected_one_organizer_works(): void
    {
        $organizer = $this->makeOrganizer('Jedan');

        $this->actingAs($this->user)->post(route('newsletter.subscribe'), [
            'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            'organizer_ids' => [$organizer->id],
        ])->assertRedirect(route('newsletter.settings'));

        $subscription = $this->user->fresh()->newsletterSubscription;
        $this->assertTrue($subscription->usesSelectedOrganizerScope());
        $this->assertEqualsCanonicalizing([$organizer->id], $subscription->organizers->pluck('id')->all());
        $this->assertFalse($subscription->include_without_organizer);
        Mail::assertNothingSent();
    }

    public function test_first_subscribe_selected_multiple_organizers_works(): void
    {
        $one = $this->makeOrganizer('Jedan');
        $two = $this->makeOrganizer('Dva');

        $this->actingAs($this->user)->post(route('newsletter.subscribe'), [
            'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            'organizer_ids' => [$one->id, $two->id],
        ])->assertRedirect(route('newsletter.settings'));

        $subscription = $this->user->fresh()->newsletterSubscription;
        $this->assertEqualsCanonicalizing([$one->id, $two->id], $subscription->organizers->pluck('id')->all());
    }

    public function test_first_subscribe_only_bez_organizatora_works(): void
    {
        $this->actingAs($this->user)->post(route('newsletter.subscribe'), [
            'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            'include_without_organizer' => '1',
        ])->assertRedirect(route('newsletter.settings'));

        $subscription = $this->user->fresh()->newsletterSubscription;
        $this->assertTrue($subscription->include_without_organizer);
        $this->assertCount(0, $subscription->organizers);
        $this->assertTrue($subscription->isActive());
    }

    public function test_selected_empty_is_invalid_and_does_not_unsubscribe_or_create(): void
    {
        $this->actingAs($this->user)
            ->from(route('newsletter.settings'))
            ->post(route('newsletter.subscribe'), [
                'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            ])
            ->assertRedirect(route('newsletter.settings'))
            ->assertSessionHasErrors('organizer_ids');

        $this->assertDatabaseCount('newsletter_subscriptions', 0);

        $subscription = $this->makeActiveSelected([$this->makeOrganizer('Org')->id]);
        $this->actingAs($this->user)
            ->from(route('newsletter.settings'))
            ->patch(route('newsletter.update'), [
                'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            ])
            ->assertRedirect(route('newsletter.settings'))
            ->assertSessionHasErrors('organizer_ids');

        $fresh = $subscription->fresh();
        $this->assertTrue($fresh->isActive());
        $this->assertCount(1, $fresh->organizers);
    }

    public function test_duplicate_subscribe_does_not_create_second_row(): void
    {
        $this->actingAs($this->user)->post(route('newsletter.subscribe'), [
            'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
        ]);
        $id = $this->user->fresh()->newsletterSubscription->id;

        $this->actingAs($this->user)->post(route('newsletter.subscribe'), [
            'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
        ])->assertRedirect(route('newsletter.settings'));

        $this->assertDatabaseCount('newsletter_subscriptions', 1);
        $this->assertSame($id, $this->user->fresh()->newsletterSubscription->id);
    }

    public function test_posted_user_id_cannot_target_another_user(): void
    {
        $other = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
        $this->makeActiveSubscription($other);

        $this->actingAs($this->user)
            ->post(route('newsletter.subscribe'), [
                'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
                'user_id' => $other->id,
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertTrue($other->fresh()->newsletterSubscription->isActive());
        $this->assertNull($this->user->fresh()->newsletterSubscription);
    }

    public function test_posted_email_cannot_target_another_user(): void
    {
        $other = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->actingAs($this->user)
            ->post(route('newsletter.subscribe'), [
                'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
                'email' => $other->email,
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('newsletter_subscriptions', 0);
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_active_user_can_edit_selected_prefs_without_changing_status_or_subscribed_at(): void
    {
        $one = $this->makeOrganizer('Jedan');
        $two = $this->makeOrganizer('Dva');
        $subscription = $this->makeActiveSelected([$one->id]);
        $subscribedAt = $subscription->subscribed_at->format('Y-m-d H:i:s');

        $this->actingAs($this->user)->patch(route('newsletter.update'), [
            'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            'organizer_ids' => [$two->id],
            'include_without_organizer' => '1',
        ])->assertRedirect(route('newsletter.settings'))
            ->assertSessionHas('success', NewsletterSubscriptionManager::MESSAGE_UPDATED);

        $fresh = $subscription->fresh();
        $this->assertTrue($fresh->isActive());
        $this->assertSame($subscribedAt, $fresh->subscribed_at->format('Y-m-d H:i:s'));
        $this->assertEqualsCanonicalizing([$two->id], $fresh->organizers->pluck('id')->all());
        $this->assertTrue($fresh->include_without_organizer);
        Mail::assertNothingSent();
    }

    public function test_selected_to_all_clears_non_empty_pivot_atomically(): void
    {
        $one = $this->makeOrganizer('Jedan');
        $two = $this->makeOrganizer('Dva');
        $subscription = $this->makeActiveSelected([$one->id, $two->id], true);

        $this->actingAs($this->user)->patch(route('newsletter.update'), [
            'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
            'organizer_ids' => [$one->id, $two->id],
            'include_without_organizer' => '1',
        ])->assertRedirect(route('newsletter.settings'));

        $fresh = $subscription->fresh();
        $this->assertTrue($fresh->usesAllEventsScope());
        $this->assertTrue($fresh->isActive());
        $this->assertFalse($fresh->include_without_organizer);
        $this->assertCount(0, $fresh->organizers);
        $this->assertDatabaseCount('newsletter_subscription_organizers', 0);
        Mail::assertNothingSent();
    }

    public function test_all_to_selected_stores_only_new_selection(): void
    {
        $one = $this->makeOrganizer('Jedan');
        $two = $this->makeOrganizer('Dva');
        $subscribe = $this->actingAs($this->user)->post(route('newsletter.subscribe'), [
            'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
        ]);
        $subscribe->assertRedirect(route('newsletter.settings'));

        $update = $this->actingAs($this->user)->patch(route('newsletter.update'), [
            'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            'organizer_ids' => [$two->id],
        ]);
        $update->assertRedirect(route('newsletter.settings'));
        $update->assertSessionHas('success', NewsletterSubscriptionManager::MESSAGE_UPDATED);

        $subscription = $this->user->fresh()->newsletterSubscription;
        $this->assertTrue($subscription->usesSelectedOrganizerScope());
        $this->assertEqualsCanonicalizing([$two->id], $subscription->organizers->pluck('id')->all());
        $this->assertFalse($subscription->organizers->contains($one));
    }

    public function test_selected_to_selected_syncs_pivot(): void
    {
        $one = $this->makeOrganizer('Jedan');
        $two = $this->makeOrganizer('Dva');
        $three = $this->makeOrganizer('Tri');
        $this->makeActiveSelected([$one->id, $two->id]);

        $this->actingAs($this->user)->patch(route('newsletter.update'), [
            'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            'organizer_ids' => [$two->id, $three->id],
        ]);

        $this->assertEqualsCanonicalizing(
            [$two->id, $three->id],
            $this->user->fresh()->newsletterSubscription->organizers->pluck('id')->all()
        );
    }

    public function test_unsubscribe_requires_explicit_confirmation_and_keeps_row(): void
    {
        $organizer = $this->makeOrganizer('Odjava');
        $subscription = $this->makeActiveSelected([$organizer->id], true);
        $id = $subscription->id;

        $this->actingAs($this->user)
            ->from(route('newsletter.settings'))
            ->post(route('newsletter.unsubscribe'))
            ->assertRedirect(route('newsletter.settings'))
            ->assertSessionHasErrors('confirm_unsubscribe');

        $this->assertTrue($subscription->fresh()->isActive());

        $html = $this->actingAs($this->user)->get(route('newsletter.settings'))->getContent();
        $this->assertStringContainsString('confirm(', $html);
        $this->assertStringContainsString('confirm_unsubscribe', $html);
        $this->assertStringContainsString('Odjavi se', $html);

        $this->actingAs($this->user)
            ->post(route('newsletter.unsubscribe'), ['confirm_unsubscribe' => '1'])
            ->assertRedirect(route('newsletter.settings'))
            ->assertSessionHas('success', NewsletterSubscriptionManager::MESSAGE_UNSUBSCRIBED);

        $fresh = NewsletterSubscription::query()->find($id);
        $this->assertNotNull($fresh);
        $this->assertTrue($fresh->isUnsubscribed());
        $this->assertNull($fresh->scope_mode);
        $this->assertFalse($fresh->include_without_organizer);
        $this->assertCount(0, $fresh->organizers);
        $this->assertNotNull($fresh->unsubscribed_at);
        $this->assertNull($fresh->unsubscribe_token);
        Mail::assertNothingSent();
    }

    public function test_reactivation_uses_same_row_requires_new_scope_and_does_not_restore_pivot(): void
    {
        $one = $this->makeOrganizer('Stari');
        $two = $this->makeOrganizer('Novi');
        $subscription = $this->makeActiveSelected([$one->id], true);
        $id = $subscription->id;

        $this->actingAs($this->user)->post(route('newsletter.unsubscribe'), ['confirm_unsubscribe' => '1']);

        $this->actingAs($this->user)
            ->from(route('newsletter.settings'))
            ->post(route('newsletter.subscribe'), [
                'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            ])
            ->assertSessionHasErrors('organizer_ids');

        $this->assertTrue($subscription->fresh()->isUnsubscribed());
        $this->assertCount(0, $subscription->fresh()->organizers);

        $this->actingAs($this->user)->post(route('newsletter.subscribe'), [
            'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            'organizer_ids' => [$two->id],
        ])->assertSessionHas('success', NewsletterSubscriptionManager::MESSAGE_SUBSCRIBED);

        $fresh = $this->user->fresh()->newsletterSubscription;
        $this->assertSame($id, $fresh->id);
        $this->assertTrue($fresh->isActive());
        $this->assertNull($fresh->unsubscribed_at);
        $this->assertEqualsCanonicalizing([$two->id], $fresh->organizers->pluck('id')->all());
        $this->assertFalse($fresh->include_without_organizer);
        $this->assertDatabaseCount('newsletter_subscriptions', 1);
        Mail::assertNothingSent();
    }

    public function test_inactive_organizer_previously_selected_is_preserved_and_new_inactive_cannot_be_added(): void
    {
        $saved = $this->makeOrganizer('Sačuvan');
        $otherInactive = $this->makeOrganizer('Novi neaktivan');
        $subscription = $this->makeActiveSelected([$saved->id]);
        $saved->forceFill(['status' => CulturalOrganizer::STATUS_DEACTIVATED])->save();
        $otherInactive->forceFill(['status' => CulturalOrganizer::STATUS_DEACTIVATED])->save();

        $this->actingAs($this->user)
            ->get(route('newsletter.settings'))
            ->assertOk()
            ->assertSee($saved->naziv, false)
            ->assertSee('(neaktivan)', false)
            ->assertDontSee($otherInactive->naziv, false);

        $this->actingAs($this->user)->patch(route('newsletter.update'), [
            'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            'organizer_ids' => [$saved->id],
        ])->assertRedirect(route('newsletter.settings'));

        $this->assertTrue($subscription->fresh()->organizers->contains('id', $saved->id));

        $this->actingAs($this->user)
            ->patch(route('newsletter.update'), [
                'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
                'organizer_ids' => [$saved->id, $otherInactive->id],
            ])
            ->assertSessionHasErrors('organizer_ids');

        $this->assertFalse($subscription->fresh()->organizers->contains('id', $otherInactive->id));
    }

    public function test_new_organizer_is_not_auto_added_to_selected_or_all_events(): void
    {
        $existing = $this->makeOrganizer('Postojeci');
        $this->makeActiveSelected([$existing->id]);

        $this->makeOrganizer('Novi');

        $this->assertEqualsCanonicalizing(
            [$existing->id],
            $this->user->fresh()->newsletterSubscription->organizers->pluck('id')->all()
        );

        $this->actingAs($this->user)->patch(route('newsletter.update'), [
            'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
        ]);
        $this->makeOrganizer('Jos noviji');

        $allEvents = $this->user->fresh()->newsletterSubscription;
        $this->assertTrue($allEvents->usesAllEventsScope());
        $this->assertCount(0, $allEvents->organizers);
    }

    public function test_get_routes_are_read_only_and_mutations_use_post_or_patch(): void
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();

        $this->assertSame(['GET', 'HEAD'], $routes->getByName('newsletter.settings')->methods());
        $this->assertSame(['POST'], $routes->getByName('newsletter.subscribe')->methods());
        $this->assertSame(['PATCH'], $routes->getByName('newsletter.update')->methods());
        $this->assertSame(['POST'], $routes->getByName('newsletter.unsubscribe')->methods());

        $this->actingAs($this->user)->get(route('newsletter.unsubscribe'))->assertStatus(405);
        $this->assertDatabaseCount('newsletter_subscriptions', 0);
    }

    public function test_legacy_arbitrary_email_path_is_not_reachable_and_does_not_write_legacy_table(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('cultural-calendar.newsletter.store'));

        $this->actingAs($this->user)
            ->post('/kalendar-kulture/newsletter', [
                'email' => 'someone-else@example.com',
                'unsubscribe' => '1',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('newsletter_subscribers', 0);
        $this->assertDatabaseCount('newsletter_subscriptions', 0);
        Mail::assertNothingSent();
    }

    public function test_calendar_index_no_longer_exposes_arbitrary_email_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->assertDontSee('name="email"', false)
            ->assertDontSee('Odjavi me', false)
            ->assertSee(route('newsletter.settings'), false);
    }

    public function test_csrf_token_is_present_on_settings_forms(): void
    {
        $this->makeActiveSubscription($this->user);

        $html = $this->actingAs($this->user)->get(route('newsletter.settings'))->getContent();
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="_method"', $html);
        $this->assertStringContainsString('value="PATCH"', $html);
    }

    private function makeActiveSubscription(User $user): NewsletterSubscription
    {
        return NewsletterSubscription::query()->create([
            'user_id' => $user->id,
            'status' => NewsletterSubscription::STATUS_ACTIVE,
            'scope_mode' => NewsletterSubscription::SCOPE_ALL_EVENTS,
            'include_without_organizer' => false,
            'subscribed_at' => now(),
            'unsubscribe_token' => str_repeat('a', 64),
        ]);
    }

    /**
     * @param  list<int>  $organizerIds
     */
    private function makeActiveSelected(array $organizerIds, bool $includeWithout = false): NewsletterSubscription
    {
        $subscription = NewsletterSubscription::query()->create([
            'user_id' => $this->user->id,
            'status' => NewsletterSubscription::STATUS_ACTIVE,
            'scope_mode' => NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            'include_without_organizer' => $includeWithout,
            'subscribed_at' => now(),
            'unsubscribe_token' => str_repeat('c', 64),
        ]);
        $subscription->organizers()->attach($organizerIds);

        return $subscription;
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
