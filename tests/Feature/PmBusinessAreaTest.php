<?php

namespace Tests\Feature;

use App\Identity\CanonicalIdentityWriter;
use App\Models\Application;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MakesIdentitySnapshots;
use Tests\Support\MakesPmHistoryFixtures;
use Tests\TestCase;

class PmBusinessAreaTest extends TestCase
{
    use DatabaseTransactions, MakesIdentitySnapshots, MakesPmHistoryFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config(['identity.canonical_read' => true, 'identity.canonical_write' => true]);
    }

    public static function forms(): array
    {
        return ['M1a' => ['fizicko_lice'], 'M1b' => ['privredno_drustvo']];
    }

    #[DataProvider('forms')]
    public function test_whitespace_area_is_not_complete(string $type): void
    {
        [, $application] = $this->fixture($type);
        $this->assertTrue($application->isObrazacComplete(), 'The other M1 fields must be complete.');
        $application->update(['business_area' => '   ']);
        $this->assertFalse($application->fresh()->isObrazacComplete());
    }

    public static function newDraftAreas(): array
    {
        $cases = [];
        foreach (self::forms() as $form => [$type]) {
            foreach (['omitted' => [false, null], 'null' => [true, null], 'empty' => [true, ''], 'spaces' => [true, '   ']] as $label => [$sent, $area]) {
                $cases[$form.' '.$label] = [$type, $sent, $area];
            }
        }
        return $cases;
    }

    #[DataProvider('newDraftAreas')]
    public function test_new_draft_accepts_empty_area(string $type, bool $sent, ?string $area): void
    {
        [$user, $existing] = $this->fixture($type);
        // A new competition exercises INSERT rather than updating the fixture's application.
        $competition = $existing->competition->replicate();
        $competition->save();
        $intent = $type === 'fizicko_lice'
            ? ['planned_intent' => \App\Support\KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR]
            : ['planned_intent' => \App\Support\KnApplicationStartContext::INTENT_PLANNED_COMPANY, 'planned_company_form' => 'doo'];
        $start = $this->actingAs($user)->post(route('applications.start', $competition), $intent)->assertRedirect();
        parse_str((string) parse_url($start->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('start_token', $query);
        $payload = ['save_as_draft' => '1', 'start_context_token' => $query['start_token'], 'business_plan_name' => 'New draft'];
        if ($sent) $payload['business_area'] = $area;
        $this->post(route('applications.store', $competition), $payload)->assertSessionHasNoErrors();
        $saved = Application::where('competition_id', $competition->id)->where('user_id', $user->id)->firstOrFail();
        $this->assertSame($type, $saved->applicant_type);
        $this->assertNull($saved->business_area);
        $this->assertSame('draft', $saved->status);
    }

    public static function draftEdits(): array
    {
        $cases = [];
        foreach (self::forms() as $form => [$type]) {
            foreach (['null' => null, 'empty' => '', 'spaces' => '   ', 'text' => '  Nova oblast  ', 'zero text' => '0', '255 characters' => str_repeat('a', 255)] as $label => $area) {
                $cases[$form.' '.$label] = [$type, $area];
            }
        }
        return $cases;
    }

    #[DataProvider('draftEdits')]
    public function test_existing_draft_saves_explicit_text_or_clear(string $type, ?string $area): void
    {
        [$user, $application] = $this->fixture($type);
        $this->saveDraft($user, $application, ['business_area' => $area])->assertSessionHasNoErrors();
        $expected = $area === null || trim($area) === '' ? null : trim($area);
        $this->assertSame($expected, $application->fresh()->business_area);
        $this->assertSame('draft', $application->fresh()->status);
    }

    #[DataProvider('forms')]
    public function test_omitted_area_preserves_the_existing_draft_value(string $type): void
    {
        [$user, $application] = $this->fixture($type);
        $this->saveDraft($user, $application, [])->assertSessionHasNoErrors();
        $this->assertSame('Usluge', $application->fresh()->business_area);
    }

    public static function emptyFinalAreas(): array
    {
        $cases = [];
        foreach (self::forms() as $form => [$type]) {
            foreach (['null' => null, 'empty' => '', 'spaces' => '   '] as $label => $area) {
                foreach ([false, true] as $confirmed) $cases[$form.' '.$label.' confirmations '.(int) $confirmed] = [$type, $area, $confirmed];
            }
        }
        return $cases;
    }

    #[DataProvider('emptyFinalAreas')]
    public function test_empty_saved_area_blocks_final_even_with_both_confirmations(string $type, ?string $area, bool $confirmed): void
    {
        [$user, $application] = $this->fixture($type);
        $application->update(['business_area' => $area]); // Covers older stored whitespace as well as HTTP-normalized NULL.
        $before = $application->fresh()->getRawOriginal();
        $payload = $confirmed ? ['confirm_incomplete_forms' => '1', 'confirm_missing_documents' => '1'] : [];
        $this->actingAs($user)->post(route('applications.final-submit', $application), $payload)
            ->assertSessionHasErrors(['error' => \App\Http\Controllers\ApplicationController::PM_M1_INCOMPLETE_MESSAGE])
            ->assertSessionMissing('success');
        $this->assertSame($before, $application->fresh()->getRawOriginal());
    }

    public static function validFinalAreas(): array
    {
        $cases = [];
        foreach (self::forms() as $form => [$type]) {
            foreach (['text' => 'Usluge', 'zero text' => '0', '255 characters' => str_repeat('a', 255)] as $label => $area) {
                $cases[$form.' '.$label] = [$type, $area];
            }
        }
        return $cases;
    }

    #[DataProvider('validFinalAreas')]
    public function test_valid_area_allows_final_and_keeps_historical_identity(string $type, string $area): void
    {
        [$user, $application] = $this->fixture($type);
        $application->update(['business_area' => $area]);
        $names = $application->only(['physical_person_name', 'doo_name', 'founder_name', 'director_name']);
        $this->assertTrue($application->fresh()->isObrazacComplete());
        $this->actingAs($user)->post(route('applications.final-submit', $application), [
            'confirm_incomplete_forms' => '1', 'confirm_missing_documents' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertSame($area, $application->fresh()->business_area);
        $this->assertSame($names, $application->fresh()->only(array_keys($names)));
    }

    #[DataProvider('forms')]
    public function test_more_than_255_characters_is_rejected_without_partial_save(string $type): void
    {
        [$user, $application] = $this->fixture($type);
        $before = $application->fresh()->getRawOriginal();
        $this->saveDraft($user, $application, ['business_area' => str_repeat('a', 256)])->assertSessionHasErrors('business_area');
        $this->assertSame($before, $application->fresh()->getRawOriginal());
    }

    #[DataProvider('forms')]
    public function test_submitted_application_rejects_regular_area_edits(string $type): void
    {
        [$user, $application] = $this->fixture($type);
        $this->actingAs($user)->post(route('applications.final-submit', $application), [
            'confirm_incomplete_forms' => '1', 'confirm_missing_documents' => '1',
        ])->assertSessionHasNoErrors();
        $before = $application->fresh()->getRawOriginal();
        $this->saveDraft($user, $application, ['business_area' => ''])->assertSessionHasErrors('error');
        $this->assertSame($before, $application->fresh()->getRawOriginal());
    }

    public function test_women_draft_keeps_its_previous_empty_field_behavior(): void
    {
        [$user, $application] = $this->fixture('fizicko_lice');
        $application->competition->update(['type' => 'zensko']);
        $this->saveDraft($user, $application, ['business_area' => null])->assertSessionHasNoErrors();
        $this->assertSame('Usluge', $application->fresh()->business_area);
    }

    public static function otherProfiles(): array
    {
        return [['zensko'], [\App\Services\Competitions\Profile\CompetitionProfileResolver::TYPE_CANONICAL]];
    }

    #[DataProvider('otherProfiles')]
    public function test_other_profiles_keep_their_previous_completeness_behavior(string $profile): void
    {
        [, $application] = $this->fixture('fizicko_lice');
        $application->competition->update(['type' => $profile]);
        $application->unsetRelation('competition');
        $application->business_area = '   ';
        $this->assertTrue($application->isObrazacComplete());
        $application->business_area = '0';
        $this->assertFalse($application->isObrazacComplete());
    }

    public function test_mmsp_keeps_its_existing_separate_store_path(): void
    {
        [$user, $application] = $this->fixture('fizicko_lice');
        $application->competition->update(['type' => \App\Services\Competitions\Profile\CompetitionProfileResolver::TYPE_CANONICAL]);
        $before = $application->fresh()->getRawOriginal();
        $this->saveDraft($user, $application, ['business_area' => null])->assertSessionHasErrors('error');
        $this->assertSame($before, $application->fresh()->getRawOriginal());
    }

    private function saveDraft($user, Application $application, array $fields)
    {
        return $this->actingAs($user)->post(route('applications.store', $application->competition), array_merge([
            'save_as_draft' => '1', 'application_id' => $application->id, 'accuracy_declaration' => '1',
        ], $fields));
    }

    private function fixture(string $type): array
    {
        [$user, $application, $plan] = $this->makePmHistoryFixture($type, false);
        $application->update(['business_stage' => "zapo\u{010d}injanje"]);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, ['person' => ['city' => 'Kotor']]));
        return [$user, $application, $plan];
    }
}
