<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\LegalEntityIdentity;
use App\Models\PlatformIdentity;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Services\CanonicalIndividualScoringService;
use App\Support\ScoringProfileConfig;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmCommissionM3DisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_pm_first_session_uses_m3_and_the_application_applicant(): void
    {
        $ctx = $this->youthCommission();
        $physical = $this->application($ctx['competition'], 'fizicko_lice', [
            'physical_person_name' => 'Ana Anić',
            'redni_broj' => 7,
        ], 'Nalog Fizičko');
        $entrepreneur = $this->application($ctx['competition'], 'preduzetnik', [
            'preduzetnik_name' => 'Marko Marković',
        ], 'Nalog Preduzetnik');
        $company = $this->application($ctx['competition'], 'privredno_drustvo', [
            'registration_form' => UserType::JOINT_STOCK_COMPANY,
            'company_legal_form' => 'ad',
            'is_registered' => true,
        ], 'Nalog Društva', 'Jadran AD');

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $physical))
            ->assertOk()
            ->getContent();

        $upNumber = $ctx['competition']->fresh('upNumber')->upNumber->number;
        $this->assertStringContainsString('Obrazac M3', $html);
        $this->assertStringContainsString('LISTA ZA OCJENJIVANJE BIZNIS PLANOVA', $html);
        $this->assertStringContainsString('class="pm-m3-memorandum"', $html);
        $this->assertStringContainsString('Broj prijave: '.$upNumber.'/7', $html);
        $this->assertStringContainsString('images/srednji_grb.png', $html);
        $this->assertStringContainsString('Crna Gora', $html);
        $this->assertStringContainsString('Opština Kotor', $html);
        $this->assertStringContainsString('Sekretarijat za razvoj preduzetništva', $html);
        $this->assertStringContainsString('komunalne poslove i saobraćaj', $html);
        $this->assertStringContainsString('Stari grad 317', $html);
        $this->assertStringContainsString('privreda@kotor.me', $html);
        $this->assertStringContainsString('www.kotor.me', $html);
        $this->assertStringContainsString('size: A4', $html);
        $this->assertStringContainsString('margin: 12mm 10mm', $html);
        $this->assertStringContainsString('border: 1px solid #ccc', $html);
        $this->assertSame(1, substr_count($html, 'class="obrazac-1a-1b"'));
        $this->assertStringNotContainsString('class="youth-ranking-print-root is-blocked"', $html);
        $this->assertStringContainsString('Info dan i obuka (+1)', $html);
        $this->assertStringContainsString('Planirana registracija (+2)', $html);
        $this->assertStringContainsString('Inovativno/zeleno poslovanje (+3)', $html);
        $this->assertStringContainsString('Potvrdi Obrazac M3', $html);
        $this->assertStringContainsString('placeholder="Napomena Obrasca M3"', $html);
        $this->assertStringContainsString('value="Ana Anić"', $html);
        $this->assertStringNotContainsString('Fizičko lice -', $html);
        $this->assertStringContainsString('Podaci o podnosiocu prijave (posjeduje iskustvo, potrebna znanja i vještine, te svijest o preduzetničkim osobinama koje mora unaprijediti).', $html);
        $this->assertStringContainsString('Podnosilac prijave planira raspored poslova uz identifikaciju osoba za njihovo obavljanje.', $html);
        $this->assertStringContainsString('Usmeno obrazloženje biznis plana (podnosilac prijave je uvjerljiv i siguran u svoju biznis ideju, pokazuje visoku motivisanost za realizaciju iste i spremno odgovara na sva pitanja).', $html);
        $this->assertStringNotContainsString('Podaci o preduzetniku', $html);
        $this->assertStringNotContainsString('Podaci o podnositeljki prijave', $html);
        $this->assertStringNotContainsString('Ostalo', $html);
        $this->assertStringNotContainsString('Obrazac 3', $html);
        $this->assertStringNotContainsString(CanonicalIndividualScoringService::YOUTH_CYCLE_INCOMPLETE_MESSAGE, $html);
        $this->assertStringNotContainsString(ScoringProfileConfig::YOUTH_SCORING_LOCKED_MESSAGE, $html);
        $this->assertStringNotContainsString('Svih 10 kriterijuma je obavezno. Nakon čuvanja ocjene se zaključavaju i izmjene nijesu moguće.', $html);
        $this->assertStringNotContainsString('Dugme Štampaj otvara prozor za štampu pregledača', $html);
        $this->assertStringContainsString('window.print()', $html);
        $this->assertMatchesRegularExpression('/id="youth-save-scores"[^>]*disabled/', $html);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.store', $physical), ['criterion_1' => 5])
            ->assertForbidden();

        $entrepreneurHtml = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $entrepreneur))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('value="Marko Marković"', $entrepreneurHtml);
        $this->assertStringNotContainsString('Preduzetnik -', $entrepreneurHtml);
        $this->assertStringNotContainsString('Ostalo - Nalog Preduzetnik', $entrepreneurHtml);

        $companyHtml = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $company))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('value="Jadran AD"', $companyHtml);
        $this->assertStringNotContainsString('AD -', $companyHtml);
        $this->assertStringNotContainsString('Ostalo - Nalog Društva', $companyHtml);
        $this->assertStringNotContainsString('Nalog Društva', $companyHtml);

        $ranking = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.youth-ranking', $ctx['competition']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString(CanonicalIndividualScoringService::YOUTH_CYCLE_INCOMPLETE_MESSAGE, $ranking);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.store', $physical), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'note' => '',
            ])
            ->assertRedirect(route('evaluation.create', $physical))
            ->assertSessionHas('success', 'Eliminatorna provjera je sačuvana. Obrazac M3 još nije potvrđen.');

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.confirm', $physical), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'note' => '',
                'confirmation_acknowledged' => '1',
            ])
            ->assertRedirect(route('evaluation.create', $physical))
            ->assertSessionHas('success', 'Obrazac M3 je potvrđen.');

        $this->actingAs($ctx['chairman']->user)
            ->from(route('evaluation.create', $physical))
            ->post(route('evaluation.store', $physical), [
                'criterion_1' => 5,
                'scoring_confirmed' => '1',
            ])
            ->assertSessionHasErrors(['criterion_2', 'criterion_10']);
        $this->assertSame(0, EvaluationScore::query()->where('application_id', $physical->id)->count());
    }

    public function test_saved_m3_show_keeps_the_m1_memorandum(): void
    {
        $ctx = $this->youthCommission();
        $application = $this->application($ctx['competition'], 'fizicko_lice', [
            'physical_person_name' => 'Ana Anić',
            'redni_broj' => 4,
        ], 'Nalog Fizičko');
        $score = [
            'application_id' => $application->id,
            'commission_member_id' => $ctx['chairman']->id,
            'canonical_seat_no' => 1,
            'completed_at' => now(),
        ];
        for ($i = 1; $i <= 10; $i++) {
            $score["criterion_{$i}"] = 3;
        }
        EvaluationScore::create($score);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.show', $application))
            ->assertOk()
            ->getContent();

        $upNumber = $ctx['competition']->fresh('upNumber')->upNumber->number;
        $this->assertStringContainsString('class="pm-m3-memorandum"', $html);
        $this->assertStringContainsString('Broj prijave: '.$upNumber.'/4', $html);
        $this->assertStringContainsString('class="obrazac-1a-1b">Obrazac M3', $html);
        $this->assertStringContainsString('LISTA ZA OCJENJIVANJE BIZNIS PLANOVA', $html);
        $this->assertStringContainsString('images/srednji_grb.png', $html);
        $this->assertStringContainsString('size: A4', $html);
        $this->assertStringContainsString('margin: 12mm 10mm', $html);
        $this->assertStringNotContainsString('Obrazac 3', $html);
        $this->assertStringContainsString('value="Ana Anić"', $html);
        $this->assertStringNotContainsString('Fizičko lice -', $html);
        $this->assertStringContainsString('Podaci o podnosiocu prijave (posjeduje iskustvo, potrebna znanja i vještine, te svijest o preduzetničkim osobinama koje mora unaprijediti).', $html);
        $this->assertStringContainsString('Podnosilac prijave planira raspored poslova uz identifikaciju osoba za njihovo obavljanje.', $html);
        $this->assertStringContainsString('Usmeno obrazloženje biznis plana (podnosilac prijave je uvjerljiv i siguran u svoju biznis ideju, pokazuje visoku motivisanost za realizaciju iste i spremno odgovara na sva pitanja).', $html);
    }

    public function test_unregistered_planned_company_shows_the_applicant_on_m3(): void
    {
        $ctx = $this->youthCommission();
        $planned = $this->application($ctx['competition'], 'privredno_drustvo', [
            'company_legal_form' => 'ad',
            'registration_form' => UserType::JOINT_STOCK_COMPANY,
            'is_registered' => false,
            'doo_name' => 'Jelena Jovanović',
        ], 'Nalog Osnivača');

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $planned))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Obrazac M3', $html);
        $this->assertStringContainsString('value="Jelena Jovanović"', $html);
        $this->assertStringNotContainsString('AD -', $html);
        $this->assertStringNotContainsString('Nalog Osnivača', $html);
    }

    public function test_zp_keeps_obrazac_3_and_its_applicant_label(): void
    {
        $ctx = $this->womenCommission();
        $owner = $this->user('korisnik', 'Milica Ostalo');
        $application = Application::create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'Plan žensko',
            'applicant_type' => 'ostalo',
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ]);

        $html = $this->actingAs($ctx['chairman']->user)
            ->get(route('evaluation.create', $application))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Obrazac 3', $html);
        $this->assertStringContainsString('Potvrdi Obrazac 3', $html);
        $this->assertStringNotContainsString('pm-m3-memorandum', $html);
        $this->assertStringNotContainsString('srednji_grb.png', $html);
        $this->assertStringContainsString('margin: 10mm 8mm', $html);
        $this->assertStringContainsString('placeholder="Napomena Obrasca 3"', $html);
        $this->assertStringContainsString('value="Ostalo - Milica Ostalo"', $html);
        $this->assertStringNotContainsString('Obrazac M3', $html);
        $this->assertStringNotContainsString('Napomena Obrasca M3', $html);

        $this->actingAs($ctx['chairman']->user)
            ->post(route('evaluation.eliminatory.store', $application), [
                'criterion_1' => '1',
                'criterion_2' => '1',
                'criterion_3' => '1',
                'note' => 'Napomena',
            ])
            ->assertSessionHas('success', 'Eliminatorna provjera je sačuvana. Obrazac 3 još nije potvrđen.');
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember}
     */
    private function youthCommission(): array
    {
        return $this->commission('omladinsko', 3, [1 => 'predsjednik', 2 => 'clan', 3 => 'clan']);
    }

    /**
     * @return array{competition: Competition, chairman: CommissionMember}
     */
    private function womenCommission(): array
    {
        $types = ['opstina', 'opstina', 'opstina', 'udruzenje', 'zene_mreza'];
        $positions = [];
        foreach ($types as $index => $type) {
            $positions[$index + 1] = ['position' => $index === 0 ? 'predsjednik' : 'clan', 'member_type' => $type];
        }

        return $this->commission('zensko', 5, $positions);
    }

    /**
     * @param  array<int, string|array{position: string, member_type: string}>  $seats
     * @return array{competition: Competition, chairman: CommissionMember}
     */
    private function commission(string $type, int $seats, array $seatMap): array
    {
        $year = (int) now()->format('Y');
        $commission = Commission::create([
            'name' => 'Komisija '.$type.' '.uniqid(),
            'year' => $year,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => $type,
        ]);
        $chairman = null;
        for ($seat = 1; $seat <= $seats; $seat++) {
            $spec = $seatMap[$seat];
            $position = is_array($spec) ? $spec['position'] : $spec;
            $memberType = is_array($spec) ? $spec['member_type'] : null;
            $user = $this->user('komisija', 'Član '.$seat);
            $member = CommissionMember::create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $position,
                'member_type' => $memberType,
                'canonical_seat_no' => $type === 'omladinsko' ? $seat : null,
                'status' => 'active',
            ]);
            if ($position === 'predsjednik') {
                $chairman = $member;
            }
        }

        $competition = Competition::create([
            'title' => 'Konkurs '.$type.' '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => $year,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission->id,
            'competition_number' => 'UP-'.$type.'-'.uniqid(),
        ]);
        UpNumber::create(['competition_id' => $competition->id, 'number' => $competition->competition_number]);

        return [
            'competition' => $competition,
            'chairman' => $chairman->fresh('user'),
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function application(
        Competition $competition,
        string $applicantType,
        array $extra,
        string $accountName,
        ?string $legalName = null,
    ): Application {
        $owner = $this->user('korisnik', $accountName);
        if ($legalName !== null) {
            $identity = PlatformIdentity::query()->create([
                'user_id' => $owner->id,
                'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
            ]);
            LegalEntityIdentity::query()->create([
                'platform_identity_id' => $identity->id,
                'legal_form' => LegalEntityIdentity::FORM_AD,
                'legal_name' => $legalName,
                'street_and_number' => 'Stari grad 1',
                'city' => 'Kotor',
            ]);
        }

        return Application::create(array_merge([
            'competition_id' => $competition->id,
            'user_id' => $owner->id,
            'business_plan_name' => 'Plan '.$applicantType,
            'applicant_type' => $applicantType,
            'business_stage' => 'započinjanje',
            'status' => 'submitted',
            'submitted_at' => now()->subDays(25),
        ], $extra));
    }

    private function user(string $role, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'address' => 'Njegoševa 1',
            'city' => 'Kotor',
        ]);
    }
}
