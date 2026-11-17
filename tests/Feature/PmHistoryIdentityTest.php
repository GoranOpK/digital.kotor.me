<?php
namespace Tests\Feature;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\YouthDecisionApplicantIdentity;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PmHistoryIdentityTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Support\MakesPmHistoryFixtures;
    use \Tests\Support\MakesIdentitySnapshots;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public static function categories(): array
    {
        return [['fizicko_lice', false, 'physical_person_name'], ['preduzetnik', true, 'preduzetnik_name'],
            ['privredno_drustvo', false, 'doo_name'], ['privredno_drustvo', true, 'founder_name']];
    }

    #[DataProvider('categories')]
    public function test_submit_freezes_saved_person_and_rejects_stale_writes(string $type, bool $registered, string $field): void
    {
        [$user, $app, $plan] = $this->fixture($type, $registered);
        $app->{$field} = 'Ime iz obrasca';
        $app->save();
        $snapshot = $registered && $type === 'privredno_drustvo'
            ? $this->plSnapshot($user, [], ['city' => 'Kotor'])
            : $this->flSnapshot($user, ['person' => ['city' => 'Kotor']]);
        (new \App\Identity\CanonicalIdentityWriter)->createForUser($user, $snapshot);
        config(['identity.canonical_read' => true, 'identity.canonical_write' => true]);
        $originalCompanyName = $plan->company_name;
        $beforeIdentity = app(YouthDecisionApplicantIdentity::class)->line($app->fresh());
        $stale = $app->fresh();
        $stalePlan = $plan->fresh();
        $this->actingAs($user)->post(route('applications.final-submit', $app), [
            'confirm_incomplete_forms' => '1', 'confirm_missing_documents' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('submitted', $app->fresh()->status);
        $this->assertSame('Ime iz obrasca', $app->fresh()->{$field});
        $this->put(route('profile.name.update'), ['first_name' => 'Marko', 'last_name' => 'Markovic'])->assertSessionHasNoErrors();
        $this->assertSame($beforeIdentity, app(YouthDecisionApplicantIdentity::class)->line($app->fresh()));
        $this->assertSame($originalCompanyName, $plan->fresh()->company_name);
        $stale->{$field} = 'Kasna izmjena';
        try { $stale->save(); $this->fail('Stale identity write accepted'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('identity', $e->errors()); }
        $stalePlan->company_name = 'Prepisan naziv';
        try { $stalePlan->save(); $this->fail('Stale plan write accepted'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('identity', $e->errors()); }
        $this->assertSame('Ime iz obrasca', $app->fresh()->{$field});
    }

    public function test_missing_saved_person_uses_marker_without_loading_user(): void
    {
        [$user, $app] = $this->fixture('fizicko_lice', false);
        $app->physical_person_name = null;
        $app->save();
        $identity = app(YouthDecisionApplicantIdentity::class);
        $this->assertNull($identity->line($app->fresh()));
        $this->assertStringContainsString('Oznaka prijave:', $identity->display($app->fresh()));
        $user->first_name = 'Promijenjeno'; $user->last_name = 'Ime'; $user->save();
        $this->assertNull($identity->line($app->fresh()));
    }

    public function test_draft_remains_editable_and_failed_submit_leaves_it_unchanged(): void
    {
        [$user, $app, $plan] = $this->fixture('fizicko_lice', false);
        $app->physical_person_name = 'Novo ime'; $app->save();
        $plan->finances_notice_confirmed = false; $plan->save();
        $before = $app->fresh()->getRawOriginal();
        $this->actingAs($user)->post(route('applications.final-submit', $app))->assertSessionHasErrors();
        $this->assertSame($before, $app->fresh()->getRawOriginal());
    }

    public function test_women_model_updates_are_not_subject_to_pm_guard(): void
    {
        [$user, $app, $plan] = $this->fixture('fizicko_lice', false);
        $app->competition->update(['type' => 'zensko']);
        $app->update(['status' => 'submitted']);
        $app->update(['physical_person_name' => 'ZP dozvoljeni servisni upis']);
        $plan->update(['company_name' => 'ZP naziv']);
        $this->assertSame('ZP dozvoljeni servisni upis', $app->fresh()->physical_person_name);
    }

    public function test_rejected_identity_is_not_overwritten_by_profile_at_final(): void
    {
        [$user, $app, $plan] = $this->fixture('fizicko_lice', false);
        $app->update(['physical_person_name' => $app->business_plan_name]);
        $this->actingAs($user)->post(route('applications.final-submit', $app), ['confirm_incomplete_forms' => '1', 'confirm_missing_documents' => '1'])
            ->assertSessionHasErrors('physical_person_name');
        $this->assertSame('draft', $app->fresh()->status);
    }

    public function test_cancelled_final_status_save_does_not_report_success(): void
    {
        [$user, $app] = $this->fixture('fizicko_lice', false);
        $event = 'eloquent.updating: '.Application::class;
        $dispatcher = Application::getEventDispatcher();
        $original = $dispatcher->getRawListeners()[$event] ?? [];
        $dispatcher->listen($event, fn ($saving) => $saving->id === $app->id && $saving->status === 'submitted' ? false : null);
        try {
            $this->actingAs($user)->post(route('applications.final-submit', $app), ['confirm_incomplete_forms' => '1', 'confirm_missing_documents' => '1'])
                ->assertSessionHasErrors('identity')->assertSessionMissing('success');
        } finally {
            $dispatcher->forget($event);
            foreach ($original as $listener) $dispatcher->listen($event, $listener);
        }
        $this->assertSame('draft', $app->fresh()->status);
        $this->assertNull($app->fresh()->submitted_at);
    }

    public function test_submitted_company_name_is_independent_of_later_profile_name(): void
    {
        [$user, $app, $plan] = $this->fixture('privredno_drustvo', true);
        (new \App\Identity\CanonicalIdentityWriter)->createForUser($user, $this->plSnapshot($user, [], ['city' => 'Kotor']));
        $this->actingAs($user)->post(route('applications.final-submit', $app), ['confirm_incomplete_forms' => '1', 'confirm_missing_documents' => '1'])
            ->assertSessionHasNoErrors();
        $before = app(YouthDecisionApplicantIdentity::class)->line($app->fresh());
        \App\Models\LegalEntityIdentity::whereIn('platform_identity_id', \App\Models\PlatformIdentity::where('user_id', $user->id)->select('id'))->firstOrFail()
            ->update(['legal_name' => 'Promijenjeno drustvo']);
        $this->assertSame($before, app(YouthDecisionApplicantIdentity::class)->line($app->fresh()));
        $this->assertSame('Primjer DOO', $plan->fresh()->company_name);
        $this->assertSame('Osnivac', $app->fresh()->founder_name);
        $this->assertSame('Direktor', $app->fresh()->director_name);
    }
    public function test_stale_http_store_requests_cannot_change_a_final_identity(): void
    {
        [$user, $app, $plan] = $this->fixture('fizicko_lice', false);
        $this->actingAs($user)->post(route('applications.final-submit', $app), ['confirm_incomplete_forms' => '1', 'confirm_missing_documents' => '1'])
            ->assertSessionHasNoErrors();
        $before = $app->fresh()->getRawOriginal();
        $beforePlan = $plan->fresh()->getRawOriginal();
        $this->post(route('applications.store', $app->competition), ['application_id' => $app->id, 'physical_person_name' => 'Kasni HTTP upis', 'save_as_draft' => '1'])
            ->assertSessionHasErrors();
        $this->post(route('applications.business-plan.store', $app), ['applicant_name' => 'Kasni M2 upis', 'save_as_draft' => '1'])
            ->assertSessionHasErrors();
        $this->assertSame($before, $app->fresh()->getRawOriginal());
        $this->assertSame($beforePlan, $plan->fresh()->getRawOriginal());
    }

    public function test_snapshot_guard_rejects_status_downgrade_but_allows_financial_outcome_updates(): void
    {
        [$user, $app] = $this->fixture('fizicko_lice', false);
        $app->update(['status' => 'submitted']);
        $app->update(['status' => 'evaluated', 'final_score' => 35, 'ranking_position' => 2, 'approved_amount' => 1000]);
        $this->assertSame('35.00', $app->fresh()->final_score);
        $this->assertSame(2, $app->fresh()->ranking_position);
        $app->status = 'draft';
        try { $app->save(); $this->fail('Reopening final identity was accepted'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('identity', $e->errors()); }
        $this->assertSame('evaluated', $app->fresh()->status);
    }
    public function test_service_cannot_attach_a_new_person_identity_after_final(): void
    {
        [$user, $app] = $this->fixture('fizicko_lice', false);
        $app->update(['status' => 'submitted']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        BusinessPlan::create(['application_id' => $app->id, 'applicant_name' => 'Nova osoba']);
    }
    private function fixture(string $type, bool $registered): array
    {
        return $this->makePmHistoryFixture($type, $registered);
    }
}
