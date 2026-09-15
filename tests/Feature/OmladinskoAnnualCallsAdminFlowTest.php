<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\Role;
use App\Models\UpNumber;
use App\Models\User;
use App\Support\CompetitionAnnualInstance;
use App\Support\CompetitionProgramCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class OmladinskoAnnualCallsAdminFlowTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private int $jmbSerial = 800;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_konkurs_admin_can_create_first_omladinsko_draft_with_manual_zavodni_broj(): void
    {
        $admin = $this->userWithRole('konkurs_admin');

        $response = $this->actingAs($admin)->post(route('admin.competitions.store'), [
            'title' => 'Omladinsko prvi Poziv',
            'description' => 'Opis prvog Poziva',
            'type' => 'omladinsko',
            'up_number' => '01-123/26',
            'year' => 2026,
            'budget' => '80000.00',
            'annual_budget' => '100000.00',
            'start_date' => '2026-04-01',
            'call_number' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        $created = Competition::query()->where('title', 'Omladinsko prvi Poziv')->first();
        $this->assertNotNull($created);
        $this->assertSame('draft', $created->status);
        $this->assertSame('omladinsko', $created->type);
        $this->assertSame(1, (int) $created->call_number);
        $this->assertSame('100000.00', $created->annual_budget);
        $this->assertSame('80000.00', $created->budget);
        $this->assertSame('01-123/26', $created->competition_number);
        $this->assertSame('01-123/26', $created->upNumber?->number);
        $this->assertFalse(preg_match('/^\d{8}\d+$/', (string) $created->competition_number) === 1);
        $this->assertSame(
            CompetitionProgramCatalog::STATUS_DEVELOPMENT,
            CompetitionProgramCatalog::definitions()['omladinsko']['status']
        );

        $user = $this->makeKorisnik(['jmb' => $this->nextJmb(), 'email' => 'omladinsko-public@example.test']);
        $this->actingAs($user)
            ->get(route('competitions.index'))
            ->assertOk()
            ->assertDontSee('Omladinsko prvi Poziv');
        $this->actingAs($user)
            ->get(route('competitions.show', $created))
            ->assertNotFound();
    }

    public function test_second_first_call_of_same_year_is_blocked(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $this->actingAs($admin)->post(route('admin.competitions.store'), $this->firstCallPayload())->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.competitions.create'))
            ->post(route('admin.competitions.store'), $this->firstCallPayload([
                'title' => 'Još jedan prvi Poziv',
                'up_number' => '01-124/26',
            ]))
            ->assertRedirect(route('admin.competitions.create'))
            ->assertSessionHasErrors('error');

        $this->assertSame(
            CompetitionAnnualInstance::MSG_FIRST_ALREADY_EXISTS,
            session('errors')->first('error')
        );
        $this->assertSame(1, Competition::query()->where('type', 'omladinsko')->where('year', 2026)->where('call_number', 1)->count());
    }

    public function test_second_call_button_and_action_are_blocked_before_chairman_decisions(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeOmladinskoFirst(['status' => 'published', 'commission_id' => null]);
        $this->addConfirmedAllocation($first, '60000.00');

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $first))
            ->assertOk()
            ->assertDontSee('Kreiraj drugi Poziv')
            ->assertSee(CompetitionAnnualInstance::MSG_CHAIRMAN_INCOMPLETE, false);

        $this->actingAs($admin)
            ->get(route('admin.competitions.second-call.create', $first))
            ->assertRedirect(route('admin.competitions.show', $first))
            ->assertSessionHasErrors('error');
        $this->assertSame(CompetitionAnnualInstance::MSG_CHAIRMAN_INCOMPLETE, session('errors')->first('error'));

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $first))
            ->post(route('admin.competitions.second-call.store', $first), $this->secondCallPayload())
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame(CompetitionAnnualInstance::MSG_CHAIRMAN_INCOMPLETE, session('errors')->first('error'));
        $this->assertSame(0, Competition::query()->where('call_number', 2)->count());
    }

    public function test_second_call_is_blocked_when_decisions_are_complete_but_status_is_not_completed(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeGateReadyFirstCall(['status' => 'closed']);
        $this->assertTrue($first->fresh()->hasChairmanCompletedDecisions());

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $first))
            ->assertOk()
            ->assertDontSee('Kreiraj drugi Poziv')
            ->assertSee(CompetitionAnnualInstance::MSG_FIRST_NOT_FINISHED, false);

        $this->actingAs($admin)
            ->post(route('admin.competitions.second-call.store', $first), $this->secondCallPayload())
            ->assertSessionHasErrors('error');
        $this->assertSame(CompetitionAnnualInstance::MSG_FIRST_NOT_FINISHED, session('errors')->first('error'));
    }

    public function test_second_call_is_blocked_when_completed_but_chairman_decisions_are_incomplete(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeOmladinskoFirst(['status' => 'completed', 'commission_id' => null]);
        $this->addConfirmedAllocation($first, '60000.00');
        $this->assertFalse($first->fresh()->hasChairmanCompletedDecisions());

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $first))
            ->assertOk()
            ->assertDontSee('Kreiraj drugi Poziv')
            ->assertSee(CompetitionAnnualInstance::MSG_CHAIRMAN_INCOMPLETE, false);

        $this->actingAs($admin)
            ->post(route('admin.competitions.second-call.store', $first), $this->secondCallPayload())
            ->assertSessionHasErrors('error');
        $this->assertSame(CompetitionAnnualInstance::MSG_CHAIRMAN_INCOMPLETE, session('errors')->first('error'));
    }

    public function test_second_call_is_blocked_when_remaining_after_first_is_zero(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeGateReadyFirstCall();
        $this->addConfirmedAllocation($first, '40000.00');
        $this->assertSame('0.00', app(CompetitionAnnualInstance::class)->remainingAfterFirst('omladinsko', 2026));

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $first))
            ->assertOk()
            ->assertDontSee('Kreiraj drugi Poziv')
            ->assertSee(CompetitionAnnualInstance::MSG_NO_REMAINING, false);

        $this->actingAs($admin)
            ->post(route('admin.competitions.second-call.store', $first), $this->secondCallPayload())
            ->assertSessionHasErrors('error');
        $this->assertSame(CompetitionAnnualInstance::MSG_NO_REMAINING, session('errors')->first('error'));
    }

    public function test_second_call_form_is_available_when_gate_is_open_and_inherits_first_call_fields(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeGateReadyFirstCall();
        $this->assertTrue($first->fresh()->hasChairmanCompletedDecisions());
        $this->assertTrue(app(CompetitionAnnualInstance::class)->canCreateSecondCall($first->fresh()));

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $first))
            ->assertOk()
            ->assertSee('Kreiraj drugi Poziv')
            ->assertSee('40.000,00', false)
            ->assertSee('Godišnji budžet', false);

        $this->actingAs($admin)
            ->get(route('admin.competitions.second-call.create', $first))
            ->assertOk()
            ->assertSee('Drugi Poziv')
            ->assertSee('100.000,00', false)
            ->assertSee('40.000,00', false)
            ->assertDontSee('name="call_number"', false)
            ->assertDontSee('name="type"', false)
            ->assertDontSee('name="year"', false)
            ->assertDontSee('name="annual_budget"', false);
    }

    public function test_second_call_budget_zero_and_negative_are_blocked(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeGateReadyFirstCall();

        foreach (['0', '0.00', '-1'] as $invalid) {
            $this->actingAs($admin)
                ->from(route('admin.competitions.second-call.create', $first))
                ->post(route('admin.competitions.second-call.store', $first), $this->secondCallPayload([
                    'budget' => $invalid,
                ]))
                ->assertRedirect()
                ->assertSessionHasErrors('budget');
        }

        $this->assertSame(0, Competition::query()->where('call_number', 2)->count());
    }

    public function test_second_call_budget_above_remaining_is_blocked_and_equal_remaining_is_allowed(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeGateReadyFirstCall();
        $originalBudget = $first->budget;

        $this->actingAs($admin)
            ->from(route('admin.competitions.second-call.create', $first))
            ->post(route('admin.competitions.second-call.store', $first), $this->secondCallPayload([
                'budget' => '40000.01',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame(
            CompetitionAnnualInstance::MSG_BUDGET_EXCEEDS_REMAINING,
            session('errors')->first('error')
        );

        $this->actingAs($admin)
            ->post(route('admin.competitions.second-call.store', $first), $this->secondCallPayload([
                'budget' => '40000.00',
                'up_number' => '01-200/26',
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $second = Competition::query()->where('call_number', 2)->first();
        $this->assertNotNull($second);
        $this->assertSame('draft', $second->status);
        $this->assertSame('omladinsko', $second->type);
        $this->assertSame(2026, (int) $second->year);
        $this->assertSame(2, (int) $second->call_number);
        $this->assertSame('100000.00', $second->annual_budget);
        $this->assertSame('40000.00', $second->budget);
        $this->assertSame('01-200/26', $second->competition_number);
        $this->assertSame($originalBudget, $first->fresh()->budget);
        $this->assertSame(0, $second->applications()->count());
    }

    public function test_second_attempt_and_third_call_are_blocked(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeGateReadyFirstCall();
        $this->actingAs($admin)
            ->post(route('admin.competitions.second-call.store', $first), $this->secondCallPayload())
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $first))
            ->assertOk()
            ->assertDontSee('Kreiraj drugi Poziv')
            ->assertSee('Otvori drugi Poziv');

        $this->actingAs($admin)
            ->post(route('admin.competitions.second-call.store', $first), $this->secondCallPayload([
                'title' => 'Treći pokušaj',
                'up_number' => '01-201/26',
            ]))
            ->assertSessionHasErrors('error');
        $this->assertSame(CompetitionAnnualInstance::MSG_SECOND_EXISTS, session('errors')->first('error'));
        $this->assertSame(1, Competition::query()->where('type', 'omladinsko')->where('year', 2026)->where('call_number', 2)->count());
        $this->assertSame(0, Competition::query()->where('call_number', 3)->count());
    }

    public function test_direct_post_cannot_bypass_the_second_call_gate(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeOmladinskoFirst(['status' => 'published']);

        $this->actingAs($admin)
            ->post(route('admin.competitions.second-call.store', $first), $this->secondCallPayload())
            ->assertSessionHasErrors('error');

        $this->assertSame(0, Competition::query()->where('call_number', 2)->count());
    }

    public function test_concurrent_second_call_unique_violation_is_translated(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeGateReadyFirstCall();
        $this->insertSecondCall($first, '01-198/26');

        $mock = Mockery::mock(new CompetitionAnnualInstance())->makePartial();
        $mock->shouldReceive('secondCallCreationBlockReason')->andReturn(null);
        $mock->shouldReceive('canCreateSecondCall')->andReturn(true);
        $mock->shouldReceive('validateSecondCall')->andReturnNull();
        $mock->shouldReceive('assertCallNumberAllowed')->andReturnNull();
        $this->app->instance(CompetitionAnnualInstance::class, $mock);

        $this->actingAs($admin)
            ->from(route('admin.competitions.second-call.create', $first))
            ->post(route('admin.competitions.second-call.store', $first), $this->secondCallPayload([
                'up_number' => '01-199/26',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $error = session('errors')->first('error');
        $this->assertSame(CompetitionAnnualInstance::MSG_CONCURRENT_SECOND_CALL, $error);
        $this->assertStringNotContainsString('SQLSTATE', (string) $error);
        $this->assertStringNotContainsString('Duplicate', (string) $error);
        $this->assertSame(1, Competition::query()->where('call_number', 2)->count());
    }

    public function test_published_omladinsko_call_locks_identity_fields(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeOmladinskoFirst(['status' => 'published']);

        $this->actingAs($admin)
            ->from(route('admin.competitions.edit', $first))
            ->put(route('admin.competitions.update', $first), $this->updatePayload($first, [
                'budget' => '50000.00',
                'annual_budget' => '90000.00',
                'year' => 2027,
                'up_number' => 'CHANGED',
                'type' => 'zensko',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $first->refresh();
        $this->assertSame('omladinsko', $first->type);
        $this->assertSame(2026, (int) $first->year);
        $this->assertSame(1, (int) $first->call_number);
        $this->assertSame('100000.00', $first->annual_budget);
        $this->assertSame('80000.00', $first->budget);
        $this->assertSame('UP-OML-1', $first->upNumber?->number);
    }

    public function test_second_draft_cannot_change_inherited_fields_and_publish_rechecks_gate(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $first = $this->makeGateReadyFirstCall();
        $this->actingAs($admin)
            ->post(route('admin.competitions.second-call.store', $first), $this->secondCallPayload())
            ->assertRedirect();
        $second = Competition::query()->where('call_number', 2)->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.competitions.edit', $second))
            ->put(route('admin.competitions.update', $second), $this->updatePayload($second, [
                'year' => 2027,
                'annual_budget' => '1.00',
                'type' => 'zensko',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $second->refresh();
        $this->assertSame('omladinsko', $second->type);
        $this->assertSame(2026, (int) $second->year);
        $this->assertSame(2, (int) $second->call_number);
        $this->assertSame('100000.00', $second->annual_budget);

        $first->update(['status' => 'closed']);
        $this->assertTrue($first->fresh()->hasChairmanCompletedDecisions());
        $this->assertNotSame('completed', $first->fresh()->status);

        $this->actingAs($admin)
            ->from(route('admin.competitions.show', $second))
            ->post(route('admin.competitions.publish', $second))
            ->assertRedirect()
            ->assertSessionHasErrors('error');
        $this->assertSame(CompetitionAnnualInstance::MSG_FIRST_NOT_FINISHED, session('errors')->first('error'));
        $this->assertSame('draft', $second->fresh()->status);
    }

    public function test_zensko_store_update_publish_and_close_remain_unchanged(): void
    {
        $admin = $this->userWithRole('konkurs_admin');

        $createPage = $this->actingAs($admin)->get(route('admin.competitions.create'));
        $createPage->assertOk();
        $html = $createPage->getContent();
        $this->assertStringNotContainsString('name="call_number"', $html);
        $this->assertStringContainsString('id="omladinsko-first-call-fields"', $html);
        $this->assertMatchesRegularExpression('/id="omladinsko-first-call-fields"[^>]*display:none/', $html);

        $this->actingAs($admin)->post(route('admin.competitions.store'), [
            'title' => 'Ženski konkurs',
            'description' => 'Opis',
            'type' => 'zensko',
            'up_number' => 'UP-Z-1',
            'year' => 2026,
            'budget' => '50000.00',
            'start_date' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $zensko = Competition::query()->where('title', 'Ženski konkurs')->firstOrFail();
        $this->assertSame('draft', $zensko->status);
        $this->assertNull($zensko->call_number);
        $this->assertNull($zensko->annual_budget);
        $this->assertMatchesRegularExpression('/^\d{8}\d+$/', (string) $zensko->competition_number);
        $this->assertSame('UP-Z-1', $zensko->upNumber?->number);

        $this->actingAs($admin)
            ->get(route('admin.competitions.show', $zensko))
            ->assertOk()
            ->assertDontSee('Kreiraj drugi Poziv')
            ->assertDontSee('Godišnja instanca prvog Poziva')
            ->assertDontSee('name="annual_budget"', false);

        $this->actingAs($admin)
            ->get(route('admin.competitions.edit', $zensko))
            ->assertOk()
            ->assertDontSee('Godišnji budžet');

        $this->actingAs($admin)
            ->put(route('admin.competitions.update', $zensko), $this->updatePayload($zensko, [
                'title' => 'Ženski konkurs izmijenjen',
            ]))
            ->assertRedirect(route('admin.competitions.show', $zensko))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.competitions.publish', $zensko))
            ->assertRedirect()
            ->assertSessionHas('success');

        $zensko->refresh();
        $this->assertSame('published', $zensko->status);
        $this->assertNull($zensko->call_number);
        $this->assertNull($zensko->annual_budget);

        $this->actingAs($admin)
            ->post(route('admin.competitions.close', $zensko))
            ->assertForbidden();
        $this->assertSame('published', $zensko->fresh()->status);
        $this->assertSame(0, Competition::query()->where('type', 'zensko')->whereNotNull('call_number')->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function firstCallPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Omladinsko prvi Poziv',
            'description' => 'Opis',
            'type' => 'omladinsko',
            'up_number' => '01-123/26',
            'year' => 2026,
            'budget' => '80000.00',
            'annual_budget' => '100000.00',
            'start_date' => '2026-04-01',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function secondCallPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Omladinsko drugi Poziv',
            'description' => 'Opis drugog',
            'up_number' => '01-200/26',
            'budget' => '40000.00',
            'start_date' => '2026-09-01',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(Competition $competition, array $overrides = []): array
    {
        return array_merge([
            'title' => $competition->title,
            'description' => $competition->description,
            'type' => $competition->type,
            'up_number' => $competition->upNumber?->number ?? 'UP',
            'year' => $competition->year,
            'budget' => $competition->budget,
            'annual_budget' => $competition->annual_budget,
            'start_date' => $competition->start_date?->toDateString(),
            'status' => $competition->status,
            'commission_id' => $competition->commission_id,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeGateReadyFirstCall(array $overrides = []): Competition
    {
        $first = $this->makeOmladinskoFirst($overrides);
        $this->addConfirmedAllocation($first, '60000.00');

        return $first->fresh(['commission', 'upNumber']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeOmladinskoFirst(array $overrides = []): Competition
    {
        $commission = $overrides['commission'] ?? $this->makeCommissionWithPresident();
        unset($overrides['commission']);

        if (array_key_exists('commission_id', $overrides) && $overrides['commission_id'] === null) {
            $commission = null;
        }

        $competition = Competition::create(array_merge([
            'title' => 'Prvi omladinski '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'completed',
            'year' => 2026,
            'call_number' => 1,
            'annual_budget' => '100000.00',
            'budget' => '80000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(30),
            'commission_id' => $commission?->id,
            'competition_number' => 'UP-OML-1',
        ], $overrides));

        UpNumber::create([
            'competition_id' => $competition->id,
            'number' => $competition->competition_number ?? 'UP-OML-1',
        ]);

        return $competition->fresh(['commission', 'upNumber']);
    }

    private function insertSecondCall(Competition $first, string $number): Competition
    {
        $second = Competition::create([
            'title' => 'Postojeći drugi Poziv',
            'description' => 'Opis',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(21)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'draft',
            'year' => $first->year,
            'call_number' => 2,
            'annual_budget' => $first->annual_budget,
            'budget' => '10000.00',
            'deadline_days' => 20,
            'competition_number' => $number,
        ]);

        UpNumber::create([
            'competition_id' => $second->id,
            'number' => $number,
        ]);

        return $second;
    }

    private function addConfirmedAllocation(Competition $competition, string $amount): Application
    {
        $user = $this->makeKorisnik([
            'email' => 'omladinsko-admin-'.$this->jmbSerial.'@example.test',
            'jmb' => $this->nextJmb(),
        ]);

        return Application::create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'business_plan_name' => 'Plan '.$this->jmbSerial,
            'applicant_type' => 'fizicko_lice',
            'business_stage' => 'započinjanje',
            'status' => CompetitionAnnualInstance::CONFIRMED_APPLICATION_STATUS,
            'commission_decision' => CompetitionAnnualInstance::CONFIRMED_COMMISSION_DECISION,
            'approved_amount' => $amount,
            'requested_amount' => $amount,
        ]);
    }

    private function makeCommissionWithPresident(): Commission
    {
        $commission = Commission::create([
            'name' => 'Komisija '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $user = $this->userWithRole('komisija');
        CommissionMember::create([
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'position' => 'predsjednik',
            'member_type' => 'opstina',
            'status' => 'active',
        ]);

        return $commission->fresh(['activeMembers.user']);
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

    private function nextJmb(): string
    {
        $jmb = $this->validJmb($this->jmbSerial);
        $this->jmbSerial++;

        return $jmb;
    }
}
