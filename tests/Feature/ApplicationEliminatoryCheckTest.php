<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\ApplicationEliminatoryCheckService;
use App\Mail\ApplicationEliminatoryRejectionMail;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApplicationEliminatoryCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_three_criteria_are_shown_in_order_with_default_da(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();

        $html = $this->actingAs($president->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();

        $pos1 = strpos($html, 'Dostavljena su sva potrebna dokumenta?');
        $pos2 = strpos($html, 'Dostavljen je Izvještaj o realizaciji biznis plana sa Finansijskim izvještajem');
        $pos3 = strpos($html, 'Biznis plan je vezan za prioritetne oblasti navedene u članu 10 Odluke?');

        $this->assertNotFalse($pos1);
        $this->assertNotFalse($pos2);
        $this->assertNotFalse($pos3);
        $this->assertTrue($pos1 < $pos2 && $pos2 < $pos3);

        $this->assertMatchesRegularExpression('/name="criterion_1"[^>]*value="1"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="criterion_2"[^>]*value="1"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/name="criterion_3"[^>]*value="1"[^>]*checked/', $html);
        $this->assertStringContainsString('Potvrdi Obrazac 3', $html);
        $this->assertStringContainsString('id="eliminatoryForm"', $html);
    }

    public function test_president_can_save_unconfirmed_draft(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.store', $application), $this->eliminatoryPayload([
                'criterion_1' => '0',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'note' => 'Draft napomena',
            ]))
            ->assertRedirect(route('evaluation.create', $application));

        $check = $application->fresh()->eliminatoryCheck;
        $this->assertNotNull($check);
        $this->assertNull($check->confirmed_at);
        $this->assertFalse((bool) $check->criterion_1);
        $this->assertSame('submitted', $application->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_other_member_sees_readonly_form_and_cannot_post_or_confirm(): void
    {
        [$application, $president, $member] = $this->submittedApplicationWithCommission(withMember: true);

        $html = $this->actingAs($member->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('id="eliminatoryForm"', $html);
        $this->assertStringNotContainsString('Potvrdi Obrazac 3', $html);
        $this->assertStringContainsString('Dostavljena su sva potrebna dokumenta?', $html);

        $this->actingAs($member->user)
            ->post(route('evaluation.eliminatory.store', $application), $this->eliminatoryPayload())
            ->assertForbidden();

        $this->actingAs($member->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->eliminatoryPayload([
                'confirmation_acknowledged' => '1',
            ]))
            ->assertForbidden();

        $this->assertNull($application->fresh()->eliminatoryCheck);
    }

    public function test_unconfirmed_ne_does_not_reject_or_void(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $score = $this->createMemberScore($application, $president, ['notes' => 'Ostala napomena člana']);

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.store', $application), $this->eliminatoryPayload([
                'criterion_1' => '0',
                'criterion_2' => '0',
                'criterion_3' => '1',
            ]))
            ->assertRedirect();

        $application->refresh();
        $score->refresh();

        $this->assertSame('submitted', $application->status);
        $this->assertNull($application->eliminatoryCheck->confirmed_at);
        $this->assertSame(5, $score->criterion_1);
        $this->assertSame('Ostala napomena člana', $score->notes);
        $this->assertSame(50.0, (float) $score->final_score);
        Mail::assertNothingSent();
    }

    public function test_fail_confirm_requires_note_and_acknowledgement(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $payload = $this->eliminatoryPayload([
            'criterion_1' => '0',
            'criterion_2' => '1',
            'criterion_3' => '1',
        ]);

        $this->actingAs($president->user)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.eliminatory.confirm', $application), $payload)
            ->assertRedirect(route('evaluation.create', $application))
            ->assertSessionHasErrors('note');

        $this->assertNull($application->fresh()->eliminatoryCheck?->confirmed_at);

        $this->actingAs($president->user)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.eliminatory.confirm', $application), array_merge($payload, [
                'note' => 'Obrazloženje Ne*',
            ]))
            ->assertRedirect(route('evaluation.create', $application))
            ->assertSessionHasErrors('confirmation_acknowledged');

        $this->assertNull($application->fresh()->eliminatoryCheck?->confirmed_at);
        $this->assertSame(
            ApplicationEliminatoryCheckService::FAIL_CONFIRMATION_MESSAGE,
            session('errors')->first('confirmation_acknowledged'),
        );
    }

    public function test_da_da_da_confirm_opens_scoring_and_fail_blocks_store(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertForbidden();

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->eliminatoryPayload())
            ->assertRedirect(route('evaluation.create', $application));

        $check = $application->fresh()->eliminatoryCheck;
        $this->assertNotNull($check->confirmed_at);
        $this->assertTrue($check->isConfirmedPass());
        $this->assertSame($president->id, $check->confirmed_by_commission_member_id);
        $this->assertSame($president->user_id, $check->confirmed_by_user_id);
        $this->assertSame($president->name, $check->confirmed_by_name);

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'notes' => 'Ostala napomena člana',
            ]))
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));

        $this->assertDatabaseHas('evaluation_scores', [
            'application_id' => $application->id,
            'commission_member_id' => $president->id,
            'notes' => 'Ostala napomena člana',
            'criterion_1' => 5,
        ]);
        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_confirmed_fail_blocks_scoring_does_not_reject_or_void_and_is_immutable(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $score = $this->createMemberScore($application, $president, [
            'notes' => 'Ostala napomena člana',
            'documents_complete' => false,
        ]);

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->eliminatoryPayload([
                'criterion_1' => '1',
                'criterion_2' => '0',
                'criterion_3' => '1',
                'note' => 'Ne prolazi kriterijum 2',
                'confirmation_acknowledged' => '1',
            ]))
            ->assertRedirect(route('evaluation.create', $application));

        $application->refresh();
        $score->refresh();
        $check = $application->eliminatoryCheck;

        $this->assertNotNull($check->confirmed_at);
        $this->assertTrue($check->isConfirmedFail());
        $this->assertSame('submitted', $application->status);
        $this->assertNotSame('rejected', $application->status);
        $this->assertSame(5, $score->criterion_1);
        $this->assertSame(50.0, (float) $score->final_score);
        $this->assertSame('Ostala napomena člana', $score->notes);
        Mail::assertSent(ApplicationEliminatoryRejectionMail::class);

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertForbidden();

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.store', $application), $this->eliminatoryPayload())
            ->assertForbidden();

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->eliminatoryPayload([
                'confirmation_acknowledged' => '1',
            ]))
            ->assertForbidden();

        $this->assertTrue($application->fresh()->eliminatoryCheck->isConfirmedFail());
        $this->assertFalse((bool) $application->fresh()->eliminatoryCheck->criterion_2);
    }

    public function test_readonly_confirmed_display_includes_answers_note_president_and_time(): void
    {
        [$application, $president, $member] = $this->submittedApplicationWithCommission(withMember: true);

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->eliminatoryPayload([
                'criterion_1' => '0',
                'criterion_2' => '1',
                'criterion_3' => '0',
                'note' => 'Obrazloženje Obrasca 3',
                'confirmation_acknowledged' => '1',
            ]))
            ->assertRedirect();

        $check = $application->fresh()->eliminatoryCheck;
        $when = $check->confirmed_at->format('d.m.Y. H:i');

        foreach ([$president, $member] as $viewer) {
            $html = $this->actingAs($viewer->user)
                ->get(route('evaluation.create', $application))
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString('Ne*', $html);
            $this->assertStringContainsString('Obrazloženje Obrasca 3', $html);
            $this->assertStringContainsString($president->name, $html);
            $this->assertStringContainsString($when, $html);
            $this->assertStringContainsString('Ne ispunjava eliminatorne kriterijume', $html);
            $this->assertStringNotContainsString('id="eliminatoryForm"', $html);
        }
    }

    public function test_superadmin_and_konkurs_admin_cannot_confirm_without_presidential_membership(): void
    {
        [$application] = $this->submittedApplicationWithCommission();
        $superadmin = $this->userWithRole('superadmin');
        $konkursAdmin = $this->userWithRole('konkurs_admin');

        foreach ([$superadmin, $konkursAdmin] as $actor) {
            $this->actingAs($actor)
                ->post(route('evaluation.eliminatory.confirm', $application), $this->eliminatoryPayload([
                    'confirmation_acknowledged' => '1',
                ]));

            $this->assertNull($application->fresh()->eliminatoryCheck?->confirmed_at);
        }
    }

    public function test_old_void_service_is_not_activated_through_new_flow(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $score = $this->createMemberScore($application, $president);

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'documents_complete' => '0',
            ]))
            ->assertForbidden();

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->eliminatoryPayload([
                'criterion_1' => '0',
                'note' => 'Ne*',
                'confirmation_acknowledged' => '1',
            ]));

        $score->refresh();
        $this->assertSame(5, $score->criterion_1);
        $this->assertSame('submitted', $application->fresh()->status);
        $this->assertNull($application->fresh()->final_score);
        Mail::assertSent(ApplicationEliminatoryRejectionMail::class);
    }

    public function test_ranking_and_is_ranking_formed_use_new_canon_not_documents_complete(): void
    {
        [$passApplication, $president, $member, $competition, $members] = $this->submittedApplicationWithCommission(withMember: true, returnAll: true);
        $failApplication = $this->createSubmittedApplication($competition);

        $this->confirmEliminatory($president, $passApplication, $this->eliminatoryPayload());
        $this->confirmEliminatory($president, $failApplication, $this->eliminatoryPayload([
            'criterion_1' => '0',
            'note' => 'Fail',
            'confirmation_acknowledged' => '1',
        ]));

        foreach ($members as $commissionMember) {
            $this->createMemberScore($passApplication, $commissionMember, [
                'documents_complete' => false,
                'final_score' => 40,
            ]);
        }

        $this->assertTrue($failApplication->fresh()->isEliminatoryConfirmedFail());
        $this->assertTrue($competition->fresh()->isRankingFormed());

        EvaluationScore::query()->where('application_id', $passApplication->id)->update(['documents_complete' => false]);
        $this->assertFalse($passApplication->fresh()->isRejectedForMissingDocuments());
        $this->assertTrue($competition->fresh()->isRankingFormed());

        $passApplication->update([
            'commission_decision' => 'podrzava_potpuno',
            'approved_amount' => 100,
        ]);
        $this->assertTrue($competition->fresh()->hasChairmanCompletedDecisions());
    }

    public function test_documents_complete_is_not_canonical_and_notes_stay_on_evaluation_scores(): void
    {
        [$application, $president] = $this->submittedApplicationWithCommission();
        $this->createMemberScore($application, $president, [
            'documents_complete' => false,
            'notes' => 'Ostala napomena člana',
        ]);

        $this->assertFalse($application->fresh()->isRejectedForMissingDocuments());
        $this->assertFalse($application->fresh()->isEliminatoryConfirmedFail());

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->eliminatoryPayload([
                'note' => 'Napomena Obrasca 3',
            ]))
            ->assertRedirect();

        $this->assertSame(
            'Ostala napomena člana',
            EvaluationScore::where('application_id', $application->id)->value('notes'),
        );
        $this->assertSame('Napomena Obrasca 3', $application->fresh()->eliminatoryCheck->note);
        $this->assertNotEquals(
            $application->fresh()->eliminatoryCheck->note,
            EvaluationScore::where('application_id', $application->id)->value('notes'),
        );
    }

    public function test_scoring_store_does_not_write_legacy_documents_complete(): void
    {
        [$application, $president, $member] = $this->submittedApplicationWithCommission(withMember: true);

        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $this->eliminatoryPayload())
            ->assertRedirect();

        $this->assertTrue($application->fresh()->eliminatoryCheck->isConfirmedPass());
        $this->assertFalse($application->fresh()->isRejectedForMissingDocuments());

        $this->actingAs($president->user)
            ->post(route('evaluation.store', $application), $this->scorePayload())
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));

        $newScore = EvaluationScore::where('application_id', $application->id)
            ->where('commission_member_id', $president->id)
            ->first();
        $this->assertNotNull($newScore);
        $this->assertTrue($newScore->documents_complete);

        $legacyScore = EvaluationScore::create([
            'application_id' => $application->id,
            'commission_member_id' => $member->id,
            'documents_complete' => false,
        ]);
        $this->assertFalse($legacyScore->fresh()->documents_complete);

        $this->actingAs($member->user)
            ->post(route('evaluation.store', $application), $this->scorePayload([
                'notes' => 'Ocjena člana',
            ]))
            ->assertRedirect(route('evaluation.index', ['filter' => 'evaluated']));

        $legacyScore->refresh();
        $this->assertFalse($legacyScore->documents_complete);
        $this->assertSame(5, $legacyScore->criterion_1);
        $this->assertTrue($application->fresh()->isEliminatoryConfirmedPass());
        $this->assertFalse($application->fresh()->isEliminatoryConfirmedFail());
    }

    public function test_layout_regression_keeps_existing_a4_margins_page_breaks_and_numbering(): void
    {
        $create = file_get_contents(resource_path('views/evaluation/create.blade.php'));
        $show = file_get_contents(resource_path('views/evaluation/show.blade.php'));

        $this->assertStringContainsString('@page {', $create);
        $this->assertStringContainsString('size: A4;', $create);
        $this->assertStringContainsString('margin: 10mm 8mm;', $create);
        $this->assertStringNotContainsString('margin: 12mm 10mm;', $create);
        $this->assertStringContainsString('print-segment-intro', $create);
        $this->assertStringContainsString('print-segment-bonus-page', $create);
        $this->assertStringContainsString('form-section-notes', $create);
        $this->assertStringContainsString('page-break-inside: avoid;', $create);
        $this->assertStringContainsString('1. Preduzetnica/Društvo:', $create);
        $this->assertStringContainsString('2. Naziv biznis plana:', $create);
        $this->assertStringContainsString('4. Ocjena biznis plana u brojkama:', $create);
        $this->assertStringContainsString('5. Ostale napomene:', $create);
        $this->assertStringContainsString('no-print', $create);

        $this->assertStringContainsString('size: A4;', $show);
        $this->assertStringContainsString('margin: 12mm 10mm;', $show);
        $this->assertStringNotContainsString('margin: 10mm 8mm;', $show);
        $this->assertStringContainsString('print-segment-intro', $show);
        $this->assertStringContainsString('print-segment-table', $show);
        $this->assertStringContainsString('page-break-before: always;', $show);
        $this->assertStringContainsString('1. Preduzetnica/Društvo:', $show);
        $this->assertStringContainsString('2. Naziv biznis plana:', $show);
    }

    /**
     * @return array{0: Application, 1: CommissionMember, 2?: CommissionMember, 3?: Competition, 4?: \Illuminate\Support\Collection<int, CommissionMember>}
     */
    private function submittedApplicationWithCommission(bool $withMember = false, bool $returnAll = false): array
    {
        $commission = $this->createCommissionWithMembers(5, true);
        $competition = $this->createZenskoCompetition('published', true, $commission);
        $application = $this->createSubmittedApplication($competition);
        $president = $commission->activeMembers()->where('position', 'predsjednik')->firstOrFail();
        $member = $commission->activeMembers()->where('position', 'clan')->firstOrFail();

        if ($returnAll) {
            return [$application, $president, $member, $competition, $commission->activeMembers];
        }

        if ($withMember) {
            return [$application, $president, $member];
        }

        return [$application, $president];
    }

    private function createSubmittedApplication(Competition $competition): Application
    {
        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Biznis plan '.uniqid(),
            'applicant_type' => 'preduzetnica',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);
    }

    private function eliminatoryPayload(array $overrides = []): array
    {
        return array_merge([
            'criterion_1' => '1',
            'criterion_2' => '1',
            'criterion_3' => '1',
            'note' => null,
        ], $overrides);
    }

    private function scorePayload(array $overrides = []): array
    {
        $payload = ['notes' => null, 'scoring_confirmed' => '1'];
        for ($i = 1; $i <= 10; $i++) {
            $payload["criterion_{$i}"] = 5;
        }

        return array_merge($payload, $overrides);
    }

    private function createMemberScore(Application $application, CommissionMember $member, array $overrides = []): EvaluationScore
    {
        return EvaluationScore::create(array_merge([
            'application_id' => $application->id,
            'commission_member_id' => $member->id,
            'documents_complete' => true,
            'criterion_1' => 5,
            'criterion_2' => 5,
            'criterion_3' => 5,
            'criterion_4' => 5,
            'criterion_5' => 5,
            'criterion_6' => 5,
            'criterion_7' => 5,
            'criterion_8' => 5,
            'criterion_9' => 5,
            'criterion_10' => 5,
            'final_score' => 50,
            'notes' => null,
        ], $overrides));
    }

    private function confirmEliminatory(CommissionMember $president, Application $application, array $payload): void
    {
        $this->actingAs($president->user)
            ->post(route('evaluation.eliminatory.confirm', $application), $payload)
            ->assertRedirect();
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
        ]);
    }

    private function createZenskoCompetition(string $status, bool $deadlinePassed, ?Commission $commission): Competition
    {
        $startDate = $deadlinePassed
            ? now()->subDays(30)->toDateString()
            : now()->subDays(2)->toDateString();
        $endDate = $deadlinePassed
            ? now()->subDays(5)->toDateString()
            : now()->addDays(18)->toDateString();

        $competition = Competition::create([
            'title' => 'Konkurs obrazac 3 '.uniqid(),
            'description' => 'Opis',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => 'zensko',
            'status' => $status,
            'year' => 2026,
            'budget' => 10000,
            'deadline_days' => 20,
            'published_at' => $status === 'draft' ? null : now()->subDays($deadlinePassed ? 30 : 2),
            'commission_id' => $commission?->id,
        ]);

        UpNumber::create([
            'competition_id' => $competition->id,
            'number' => 'UP-'.uniqid(),
        ]);

        return $competition->fresh();
    }

    private function createCommissionWithMembers(int $count, bool $withPresident): Commission
    {
        $commission = Commission::create([
            'name' => 'Komisija '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];

        for ($i = 0; $i < $count; $i++) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);

            CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => ($withPresident && $i === 0) ? 'predsjednik' : 'clan',
                'member_type' => $types[$i] ?? 'opstina',
                'status' => 'active',
            ]);
        }

        return $commission->fresh(['activeMembers.user']);
    }
}
