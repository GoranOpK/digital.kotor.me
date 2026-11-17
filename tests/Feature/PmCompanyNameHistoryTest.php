<?php

namespace Tests\Feature;

use App\Identity\CanonicalIdentityWriter;
use App\Models\Application;
use App\Models\LegalEntityIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Services\Competitions\YouthDecisionApplicantIdentity;
use App\Support\PmCommissionApplicantPresentation;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MakesIdentitySnapshots;
use Tests\Support\MakesPmHistoryFixtures;
use Tests\TestCase;

class PmCompanyNameHistoryTest extends TestCase
{
    use DatabaseTransactions, MakesIdentitySnapshots, MakesPmHistoryFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config(['identity.canonical_read' => true, 'identity.canonical_write' => true]);
    }

    private function fixture(bool $graph = true): array
    {
        [$user, $application, $plan] = $this->makePmHistoryFixture('privredno_drustvo', true);
        $application->update(['company_legal_form' => 'doo', 'registration_form' => \App\Support\UserType::LIMITED_LIABILITY_COMPANY, 'crps_number' => '50000001']);
        $plan->update(['company_name' => 'Firma DOO']);
        if ($graph) {
            (new CanonicalIdentityWriter)->createForUser($user, $this->plSnapshot($user, [], ['legalName' => 'Firma DOO', 'city' => 'Kotor']));
        }
        return [$user, $application, $plan];
    }

    private function submit(User $user, Application $application)
    {
        return $this->actingAs($user)->from(route('applications.show', $application))->post(route('applications.final-submit', $application), [
            'confirm_incomplete_forms' => '1', 'confirm_missing_documents' => '1',
        ]);
    }

    public function test_trim_only_comparison_accepts_matching_names_without_rewriting_either(): void
    {
        [$user, $application, $plan] = $this->fixture();
        $plan->update(['company_name' => '  Firma DOO  ']);
        $entity = LegalEntityIdentity::whereIn('platform_identity_id', PlatformIdentity::where('user_id', $user->id)->select('id'))->firstOrFail();
        $entity->update(['legal_name' => ' Firma DOO ']);
        $project = $application->business_plan_name;
        $person = $application->doo_name;
        $this->submit($user, $application)->assertSessionHasNoErrors();
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertSame('  Firma DOO  ', $plan->fresh()->company_name);
        $this->assertSame(' Firma DOO ', $entity->fresh()->legal_name);
        $this->assertSame($project, $application->fresh()->business_plan_name);
        $this->assertSame($person, $application->fresh()->doo_name);
    }

    public static function invalidNames(): array
    {
        return [[null], [''], ['   '], ['firma DOO'], ['Firma  DOO'], ['Firma D.O.O.'], ["Firma DOO\u{0107}"]];
    }

    #[DataProvider('invalidNames')]
    public function test_mismatch_or_empty_name_rejects_final_without_partial_save(?string $name): void
    {
        [$user, $application, $plan] = $this->fixture();
        $plan->update(['company_name' => $name]);
        $beforeApplication = $application->fresh()->getRawOriginal();
        $beforePlan = $plan->fresh()->getRawOriginal();
        $this->submit($user, $application)->assertSessionHasErrors('company_name')->assertSessionMissing('success');
        $this->assertSame($beforeApplication, $application->fresh()->getRawOriginal());
        $this->assertSame($beforePlan, $plan->fresh()->getRawOriginal());
        $this->assertSame('Firma DOO', LegalEntityIdentity::whereIn('platform_identity_id', PlatformIdentity::where('user_id', $user->id)->select('id'))->value('legal_name'));
    }

    public static function unreadableGraphs(): array
    {
        return [['missing'], ['missing_person'], ['cipher'], ['blank_name'], ['wrong_branch']];
    }

    #[DataProvider('unreadableGraphs')]
    public function test_unreadable_canonical_identity_cannot_bypass_final(string $kind): void
    {
        [$user, $application, $plan] = $this->fixture(! in_array($kind, ['missing', 'wrong_branch', 'missing_person'], true));
        $platformId = PlatformIdentity::where('user_id', $user->id)->value('id');
        if ($kind === 'missing_person') {
            $platform = PlatformIdentity::create(['user_id' => $user->id, 'subject_type' => 'legal_entity']);
            LegalEntityIdentity::create(['platform_identity_id' => $platform->id, 'legal_form' => 'doo', 'legal_name' => 'Firma DOO', 'pib' => '12345672', 'street_and_number' => 'Test 1', 'city' => 'Kotor']);
        } elseif ($kind === 'cipher') {
            DB::table('legal_entity_authorized_persons')->whereIn('legal_entity_identity_id', LegalEntityIdentity::where('platform_identity_id', $platformId)->select('id'))->update(['jmb_encrypted' => 'invalid-test-ciphertext']);
        } elseif ($kind === 'blank_name') {
            LegalEntityIdentity::where('platform_identity_id', $platformId)->firstOrFail()->update(['legal_name' => '   ']);
        } elseif ($kind === 'wrong_branch') {
            (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user));
        }
        $this->submit($user, $application)->assertSessionHasErrors('company_name')->assertSessionMissing('success');
        $this->assertSame('draft', $application->fresh()->status);
        $this->assertNull($application->fresh()->submitted_at);
        $this->assertSame('Firma DOO', $plan->fresh()->company_name);
    }

    public function test_m2_shows_both_names_and_existing_correction_links_without_a_new_input(): void
    {
        [$user, $application, $plan] = $this->fixture();
        $plan->update(['company_name' => 'Naziv iz M2']);
        $response = $this->actingAs($user)->get(route('applications.business-plan.create', $application));
        $response->assertOk()->assertSee('Firma DOO')->assertSee('Naziv iz M2')->assertSee(route('profile.edit'), false);
        $this->assertSame(1, substr_count($response->getContent(), 'name="company_name"'));
        $this->submit($user, $application)->assertSessionHasErrors('company_name');
        $this->get(route('applications.show', $application))->assertOk()->assertSee('Firma DOO')->assertSee('Naziv iz M2')
            ->assertSee(route('applications.business-plan.create', $application), false)->assertSee(route('profile.edit'), false);
    }

    public function test_history_reads_only_m2_even_if_the_live_identity_is_renamed_or_missing(): void
    {
        [$user, $application, $plan] = $this->fixture();
        $this->submit($user, $application)->assertSessionHasNoErrors();
        $identity = app(YouthDecisionApplicantIdentity::class);
        $before = $identity->line($application->fresh());
        $this->assertStringContainsString('Firma DOO', $before);
        $this->assertSame('Firma DOO', PmCommissionApplicantPresentation::subjectName($application->fresh()));
        LegalEntityIdentity::whereIn('platform_identity_id', PlatformIdentity::where('user_id', $user->id)->select('id'))->firstOrFail()->update(['legal_name' => 'Drugi naziv profila']);
        $this->assertSame($before, $identity->line($application->fresh()));
        $history = $application->fresh();
        $history->setRelation('user', null);
        $this->assertSame($before, $identity->line($history));
        $this->assertSame('Firma DOO', $plan->fresh()->company_name);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $plan->company_name = 'Naknadna izmjena'; $plan->save();
    }

    public function test_missing_historical_company_name_uses_marker_not_a_live_name(): void
    {
        [$user, $application, $plan] = $this->fixture();
        $plan->update(['company_name' => null]);
        $application->update(['status' => 'submitted']); // Synthetic incomplete historical fixture.
        $this->assertNull(app(YouthDecisionApplicantIdentity::class)->line($application->fresh()));
        $this->assertStringContainsString('Oznaka prijave:', app(YouthDecisionApplicantIdentity::class)->display($application->fresh()));
        $this->assertStringNotContainsString('Firma DOO', PmCommissionApplicantPresentation::line($application->fresh()));
    }
}
