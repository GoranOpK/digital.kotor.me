<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationPrigovor;
use App\Models\ApplicationPrigovorMappingCorrection;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\ApplicationEliminatoryCheckService;
use App\Services\YouthPrigovorMappingCorrectionService;
use App\Support\YouthPrigovorObrazlozenje;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OmladinskoPrigovor107MappingCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private int $yearSerial = 2040;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_mapping_correction_table_is_append_only_audit(): void
    {
        $this->assertTrue(Schema::hasTable('application_prigovor_mapping_corrections'));
        $this->assertTrue(Schema::hasColumns('application_prigovor_mapping_corrections', [
            'application_id',
            'prigovor_id',
            'from_criterion',
            'to_criterion',
            'before_snapshot',
            'after_snapshot',
            'reason',
            'corrected_by_commission_member_id',
            'corrected_by_user_id',
            'corrected_by_name',
            'created_at',
        ]));
        $this->assertFalse(Schema::hasColumn('application_prigovor_mapping_corrections', 'updated_at'));
    }

    public function test_correction_is_available_only_for_mismatched_application_107(): void
    {
        $ctx = $this->mismatched107Context();
        $application = $ctx['application'];

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Jednokratna korekcija mapiranja', $html);
        $this->assertStringContainsString('name="correction_reason"', $html);
        $this->assertStringContainsString('Evidentiraj korekciju mapiranja', $html);
        $this->assertStringNotContainsString('name="criterion_outcomes[1]"', $html);
        $activatedStart = strpos($html, 'Aktivirani kriterijumi i obrazloženja podnosioca:');
        $this->assertNotFalse($activatedStart);
        $activated = substr($html, $activatedStart, 800);
        $this->assertStringNotContainsString('Ako je podnosilac ranije koristio podršku, da li je dostavio M4/M4a?', $activated);
        $this->assertStringContainsString('Dostavljena je potpuna dokumentacija?', $html);
    }

    public function test_other_actor_other_application_resolved_or_corrected_cannot_use_action(): void
    {
        $ctx = $this->mismatched107Context();
        $application = $ctx['application'];
        $payload = ['correction_reason' => 'Ispravka mapiranja na aktivirani M3 kriterijum.'];

        $this->actingAs($ctx['members'][1]->user)
            ->post(route('evaluation.prigovor.correct-107-mapping', $application), $payload)
            ->assertForbidden();

        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)
            ->post(route('evaluation.prigovor.correct-107-mapping', $application), $payload)
            ->assertForbidden();

        $other = $this->mismatchedOtherApplication();
        $this->actingAs($other['chairman']->user)
            ->post(route('evaluation.prigovor.correct-107-mapping', $other['application']), $payload)
            ->assertForbidden();
        $otherHtml = $this->actingAs($other['chairman']->user)
            ->get(route('evaluation.create', $other['application']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('Evidentiraj korekciju mapiranja', $otherHtml);

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $application))
            ->post(route('evaluation.prigovor.correct-107-mapping', $application), [
                'correction_reason' => '   ',
            ])
            ->assertRedirect(route('evaluation.create', $application))
            ->assertSessionHasErrors('correction_reason');
        $this->assertNull(ApplicationPrigovorMappingCorrection::query()->where('application_id', $application->id)->first());

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.correct-107-mapping', $application), $payload)
            ->assertRedirect(route('evaluation.create', $application));

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.correct-107-mapping', $application), [
                'correction_reason' => 'Drugi pokušaj korekcije.',
            ])
            ->assertForbidden();
        $this->assertSame(1, ApplicationPrigovorMappingCorrection::query()->where('application_id', $application->id)->count());
    }

    public function test_resolved_prigovor_cannot_use_mapping_correction(): void
    {
        $ctx = $this->mismatched107Context();
        $ctx['application']->prigovor->update([
            'status' => ApplicationPrigovor::STATUS_ODBIJEN,
            'decided_at' => now(),
        ]);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.correct-107-mapping', $ctx['application']), [
                'correction_reason' => 'Ispravka nakon odluke.',
            ])
            ->assertForbidden();
        $this->assertNull(ApplicationPrigovorMappingCorrection::query()->where('application_id', $ctx['application']->id)->first());
    }

    public function test_audit_preserves_before_after_actor_reason_deadline_and_does_not_move_text(): void
    {
        $ctx = $this->mismatched107Context();
        $application = $ctx['application'];
        $m4Text = 'Tekst upisan uz neaktivirani M4/M4a.';
        $prigovor = $application->prigovor;
        $submittedAt = $prigovor->submitted_at?->toIso8601String();
        $deadline = $prigovor->komisijaDeadlineAt()->toIso8601String();
        $m3Before = $this->m3Snapshot($application);
        $statusBefore = $prigovor->status;

        $this->actingAs($ctx['chairman']->user)
            ->postJson(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Odluka prije korekcije.',
                'odluka' => 'odbijen',
            ])
            ->assertForbidden();
        $this->assertTrue($application->fresh()->prigovor->isPodnesen());

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.correct-107-mapping', $application), [
                'correction_reason' => 'Zaključani M3 aktivira samo dokumentaciju.',
            ])
            ->assertRedirect();

        $correction = ApplicationPrigovorMappingCorrection::query()
            ->where('application_id', YouthPrigovorMappingCorrectionService::APPLICATION_ID)
            ->first();
        $this->assertNotNull($correction);
        $this->assertSame(2, $correction->from_criterion);
        $this->assertSame(1, $correction->to_criterion);
        $this->assertSame($ctx['chairman']->id, $correction->corrected_by_commission_member_id);
        $this->assertSame($ctx['chairman']->user_id, $correction->corrected_by_user_id);
        $this->assertSame($ctx['chairman']->name, $correction->corrected_by_name);
        $this->assertSame('Zaključani M3 aktivira samo dokumentaciju.', $correction->reason);
        $this->assertNotNull($correction->created_at);
        $this->assertSame($submittedAt, $correction->before_snapshot['submitted_at']);
        $this->assertSame($deadline, $correction->before_snapshot['komisija_deadline_at']);
        $this->assertSame($m4Text, $correction->before_snapshot['applicant_explanation_2']);
        $this->assertSame('', $correction->before_snapshot['applicant_explanation_1']);
        $this->assertSame($m4Text, $correction->after_snapshot['applicant_explanation_2']);
        $this->assertSame('', $correction->after_snapshot['applicant_explanation_1']);
        $this->assertSame($submittedAt, $correction->after_snapshot['submitted_at']);
        $this->assertSame($deadline, $correction->after_snapshot['komisija_deadline_at']);
        $this->assertSame($statusBefore, $correction->after_snapshot['prigovor_status']);

        $fresh = $application->fresh(['prigovor', 'eliminatoryCheck']);
        $this->assertTrue($fresh->prigovor->isPodnesen());
        $this->assertSame($submittedAt, $fresh->prigovor->submitted_at?->toIso8601String());
        $this->assertSame($deadline, $fresh->prigovor->komisijaDeadlineAt()->toIso8601String());
        $this->assertTrue($fresh->prigovor->criterionIsContested(1));
        $this->assertFalse($fresh->prigovor->criterionIsContested(2));
        $this->assertNull($fresh->prigovor->criterion_2_contested);
        $this->assertSame($m4Text, YouthPrigovorObrazlozenje::explanation($fresh->prigovor->obrazlozenje, 2));
        $this->assertSame('', YouthPrigovorObrazlozenje::explanation($fresh->prigovor->obrazlozenje, 1));
        $this->assertSame($m3Before, $this->m3Snapshot($fresh));

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $fresh))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Jednokratna korekcija mapiranja evidentirana', $html);
        $this->assertStringContainsString('name="odluka"', $html);
        $this->assertStringContainsString('Prigovor prihvaćen', $html);
        $this->assertStringNotContainsString('name="criterion_outcomes', $html);
        $this->assertStringNotContainsString($m4Text, $html);
        $activatedStart = strpos($html, 'Aktivirani kriterijumi i obrazloženja podnosioca:');
        $activatedEnd = strpos($html, 'Odluka Komisije o prigovoru', (int) $activatedStart);
        $this->assertNotFalse($activatedStart);
        $this->assertNotFalse($activatedEnd);
        $activated = substr($html, $activatedStart, $activatedEnd - $activatedStart);
        $this->assertStringNotContainsString('Ako je podnosilac ranije koristio podršku, da li je dostavio M4/M4a?', $activated);
        $this->assertStringNotContainsString('Evidentiraj korekciju mapiranja', $html);
    }

    public function test_after_correction_commission_decides_only_criterion_one_and_late_document_cannot_lift(): void
    {
        $ctx = $this->mismatched107Context();
        $application = $ctx['application'];

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.correct-107-mapping', $application), [
                'correction_reason' => 'Mapiranje na zaključani M3.',
            ])
            ->assertRedirect();

        $late = ApplicationDocument::create([
            'application_id' => $application->id,
            'name' => 'kasni-izvod.pdf',
            'file_path' => 'documents/107-late.pdf',
            'document_type' => 'licna_karta',
            'is_required' => true,
        ]);
        $this->assertTrue($late->created_at->gt($application->fresh('competition')->competition->deadline));

        $this->actingAs($ctx['chairman']->user)
            ->postJson(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Pokušaj otklanjanja kasnim dokumentom.',
                'odluka' => 'prihvacen',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['odluka']);

        $this->actingAs($ctx['chairman']->user)
            ->postJson(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Pokušaj odluke o M4.',
                'criterion_outcomes' => [2 => 'otklonjen'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['odluka']);

        $stillOpen = $application->fresh(['prigovor']);
        $this->assertTrue($stillOpen->prigovor->isPodnesen());
        $this->assertSame('submitted', $stillOpen->status);
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($stillOpen));

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.prigovor.decide', $application), [
                'decision_note' => 'Kasni prilog se ne računa. Razlog dokumentacije ostaje.',
                'odluka' => 'odbijen',
            ])
            ->assertRedirect();

        $decided = $application->fresh(['prigovor']);
        $this->assertTrue($decided->prigovor->isRejected());
        $this->assertTrue($decided->prigovor->criterionRemainingIsTrue(1));
        $this->assertNull($decided->prigovor->criterion_2_remaining);
        $this->assertSame('rejected', $decided->status);
        $this->assertSame('Dostavljena je potpuna dokumentacija?', $decided->rejection_reason);
        $this->assertFalse(app(ApplicationEliminatoryCheckService::class)->scoringIsAllowed($decided));
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function mismatched107Context(): array
    {
        $ctx = $this->makeYouthContext();
        $this->rekeyApplication($ctx['application'], YouthPrigovorMappingCorrectionService::APPLICATION_ID);
        $ctx['application'] = Application::query()->findOrFail(YouthPrigovorMappingCorrectionService::APPLICATION_ID);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthFailPayload([1]))
            ->assertRedirect();

        $this->persistMismatchedYouthPrigovor($ctx['application'], 'Tekst upisan uz neaktivirani M4/M4a.');

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function mismatchedOtherApplication(): array
    {
        $ctx = $this->makeYouthContext();
        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $ctx['application']), $this->youthFailPayload([1]))
            ->assertRedirect();
        $this->persistMismatchedYouthPrigovor($ctx['application'], 'Tekst uz M4 na drugoj prijavi.');

        $this->assertNotSame(YouthPrigovorMappingCorrectionService::APPLICATION_ID, (int) $ctx['application']->id);

        return $ctx;
    }

    private function rekeyApplication(Application $application, int $id): void
    {
        if ((int) $application->id === $id) {
            return;
        }

        DB::table('applications')->where('id', $application->id)->update(['id' => $id]);
    }

    private function persistMismatchedYouthPrigovor(Application $application, string $m4Explanation): ApplicationPrigovor
    {
        return ApplicationPrigovor::create([
            'application_id' => $application->id,
            'obrazlozenje' => YouthPrigovorObrazlozenje::compose([
                1 => '',
                2 => $m4Explanation,
                3 => '',
            ]),
            'status' => ApplicationPrigovor::STATUS_PODNESEN,
            'submitted_at' => now(),
            'submitted_by_user_id' => $application->user_id,
            'eliminatory_reason_remaining' => true,
            'criterion_1_contested' => false,
            'criterion_2_contested' => true,
            'criterion_3_contested' => null,
        ]);
    }

    /**
     * @param  list<int>  $failed
     * @return array<string, mixed>
     */
    private function youthFailPayload(array $failed = [1, 2]): array
    {
        return [
            'criterion_1' => in_array(1, $failed, true) ? '0' : '1',
            'criterion_2' => in_array(2, $failed, true) ? '0' : '1',
            'criterion_3' => in_array(3, $failed, true) ? '0' : '1',
            'criterion_notes' => [
                1 => in_array(1, $failed, true) ? 'Nedostaje izvod' : '',
                2 => in_array(2, $failed, true) ? 'Nema M4' : '',
                3 => in_array(3, $failed, true) ? 'Nije prioritetna oblast' : '',
            ],
            'confirmation_acknowledged' => '1',
        ];
    }

    /**
     * @return array{criterion_1: mixed, criterion_2: mixed, criterion_3: mixed, note: ?string, confirmed_at: mixed}
     */
    private function m3Snapshot(Application $application): array
    {
        $check = $application->fresh()->eliminatoryCheck;

        return [
            'criterion_1' => $check->criterion_1,
            'criterion_2' => $check->criterion_2,
            'criterion_3' => $check->criterion_3,
            'note' => $check->note,
            'confirmed_at' => $check->confirmed_at?->timestamp,
        ];
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember, members: list<CommissionMember>, application: Application}
     */
    private function makeYouthContext(): array
    {
        $year = $this->yearSerial++;
        $commission = Commission::create([
            'name' => 'Mladi korekcija '.$year,
            'year' => $year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $komisijaRole = Role::where('name', 'komisija')->firstOrFail();
        $members = [];
        for ($seat = 1; $seat <= 3; $seat++) {
            $user = User::factory()->create([
                'role_id' => $komisijaRole->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $members[] = CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $seat === 1 ? 'predsjednik' : 'clan',
                'member_type' => null,
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
        }

        $competition = Competition::create([
            'title' => 'Omladinsko korekcija '.$year,
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => $year,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => 'UP-C-'.$year,
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        $application = Application::create([
            'competition_id' => $competition->id,
            'user_id' => $this->userWithRole('korisnik')->id,
            'business_plan_name' => 'Plan korekcija '.$year,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        return [
            'competition' => $competition->fresh(['commission.activeMembers.user']),
            'chairman' => $members[0]->fresh('user'),
            'members' => $members,
            'application' => $application,
        ];
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
}
