<?php

namespace Tests\Feature\Identity;

use App\Identity\CanonicalIdentityReader;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Runtime\CurrentIdentityResolver;
use App\Identity\Runtime\DerivedUserTypeMirror;
use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\CanonicalApplicantForm;
use App\Models\Competition;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\Role;
use App\Models\User;
use App\Security\JmbDualWrite;
use App\Security\JmbEncryptedReadService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalLegalForm;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class DomesticCompanyIdentifierProfileTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => false,
        ]);
    }

    public function test_domestic_company_saves_jmb_and_crps_without_writing_users_jmb(): void
    {
        $user = $this->domesticCompany(UserType::LIMITED_LIABILITY_COMPANY, LegalEntityIdentity::FORM_DOO, 5, 41);
        $newJmb = $this->validJmb(141);
        $newCrps = $this->validCrps(5, 141);
        $newPib = $this->validPib(141);

        $this->actingAs($user)
            ->put(route('profile.name.update'), [
                'first_name' => 'Mila',
                'last_name' => 'Milić',
                'name_context' => 'profile',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->put(route('profile.update'), $this->accountPayload($user, [
                'first_name' => 'Staro',
                'last_name' => 'Ime',
                'phone' => '+38267111000',
                'company_name' => 'Nova DOO',
                'pib' => $newPib,
                'jmb' => $newJmb,
                'crps_number' => $newCrps,
                'passport_number' => 'X999999',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $saved = (new CanonicalIdentityReader)->forUser($user->fresh());
        $person = $saved->legalEntity->authorizedPerson;
        $this->assertSame('Mila', $person->firstName);
        $this->assertSame('Milić', $person->lastName);
        $this->assertSame($newJmb, $person->jmb);
        $this->assertSame(PhysicalPersonIdentity::DOCUMENT_JMB, $person->idDocumentType);
        $this->assertNull($person->passportNumber);
        $this->assertSame($newCrps, $saved->legalEntity->crpsNumber);
        $this->assertSame('Nova DOO', $saved->legalEntity->legalName);
        $this->assertSame($newPib, $saved->legalEntity->pib);
        $this->assertSame('+38267111000', $saved->mobilePhone);
        $this->assertSame('Mila Milić', $user->fresh()->name);

        $stored = $this->authorizedPerson($user);
        $this->assertSame($newJmb, $stored->jmb);
        $this->assertNotNull($stored->jmb_encrypted);
        $this->assertNotSame($newJmb, $stored->jmb_encrypted);
        $this->assertSame($newJmb, app(JmbEncryptedReadService::class)->readValue(
            $stored->jmb_encrypted,
            null,
            'legal_entity_authorized_persons',
            $stored->id,
            'jmb/jmb_encrypted',
        ));

        $fresh = $user->fresh();
        $this->assertNull($fresh->getRawOriginal('jmb'));
        $this->assertNull($fresh->getRawOriginal('jmb_lookup'));
    }

    public function test_ad_od_and_kd_accept_the_crps_mark_for_their_legal_form(): void
    {
        foreach ([
            [UserType::JOINT_STOCK_COMPANY, LegalEntityIdentity::FORM_AD, 4, 42],
            [UserType::GENERAL_PARTNERSHIP, LegalEntityIdentity::FORM_OD, 2, 43],
            [UserType::LIMITED_PARTNERSHIP, LegalEntityIdentity::FORM_KD, 3, 44],
        ] as [$type, $form, $mark, $serial]) {
            $user = $this->domesticCompany($type, $form, $mark, $serial);
            $newJmb = $this->validJmb(200 + $serial);
            $newCrps = $this->validCrps($mark, 200 + $serial);

            $this->actingAs($user)
                ->put(route('profile.update'), $this->accountPayload($user, [
                    'user_type' => $type,
                    'jmb' => $newJmb,
                    'crps_number' => $newCrps,
                ]))
                ->assertSessionHasNoErrors();

            $saved = (new CanonicalIdentityReader)->forUser($user->fresh());
            $this->assertSame($newJmb, $saved->legalEntity->authorizedPerson->jmb);
            $this->assertSame($newCrps, $saved->legalEntity->crpsNumber);
            $this->assertNull($user->fresh()->getRawOriginal('jmb'));
        }
    }

    public function test_the_same_authorized_jmb_can_be_saved_on_two_companies(): void
    {
        $sharedJmb = $this->validJmb(151);
        $first = $this->domesticCompany(UserType::LIMITED_LIABILITY_COMPANY, LegalEntityIdentity::FORM_DOO, 5, 51);
        $second = $this->domesticCompany(UserType::JOINT_STOCK_COMPANY, LegalEntityIdentity::FORM_AD, 4, 52);

        foreach ([[$first, 5, UserType::LIMITED_LIABILITY_COMPANY], [$second, 4, UserType::JOINT_STOCK_COMPANY]] as [$user, $mark, $type]) {
            $this->actingAs($user)
                ->put(route('profile.update'), $this->accountPayload($user, [
                    'user_type' => $type,
                    'jmb' => $sharedJmb,
                    'crps_number' => $this->validCrps($mark, 300 + $mark),
                ]))
                ->assertSessionHasNoErrors();

            $saved = (new CanonicalIdentityReader)->forUser($user->fresh());
            $this->assertSame($sharedJmb, $saved->legalEntity->authorizedPerson->jmb);
            $this->assertNull($user->fresh()->getRawOriginal('jmb_lookup'));
        }
    }

    public function test_invalid_jmb_and_crps_are_rejected_and_leave_the_profile_unchanged(): void
    {
        $user = $this->domesticCompany(UserType::LIMITED_LIABILITY_COMPANY, LegalEntityIdentity::FORM_DOO, 5, 61);
        $before = (new CanonicalIdentityReader)->forUser($user);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->accountPayload($user, [
                'phone' => '+38267111061',
                'company_name' => 'Neće DOO',
                'jmb' => '1234567890123',
                'crps_number' => $this->validCrps(5, 61),
            ]))
            ->assertSessionHasErrors('jmb')
            ->assertRedirect(route('profile.edit'));

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->accountPayload($user, [
                'phone' => '+38267111062',
                'jmb' => '123',
                'crps_number' => $this->validCrps(5, 62),
            ]))
            ->assertSessionHasErrors('jmb');

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->accountPayload($user, [
                'phone' => '+38267111063',
                'jmb' => $this->validJmb(61),
                'crps_number' => $this->validCrps(1, 61),
            ]))
            ->assertSessionHasErrors('crps_number');

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->accountPayload($user, [
                'jmb' => $this->validJmb(62),
                'crps_number' => '5000000',
            ]))
            ->assertSessionHasErrors('crps_number');

        $after = (new CanonicalIdentityReader)->forUser($user->fresh());
        $this->assertSame($before->legalEntity->authorizedPerson->jmb, $after->legalEntity->authorizedPerson->jmb);
        $this->assertSame($before->legalEntity->crpsNumber, $after->legalEntity->crpsNumber);
        $this->assertSame($before->legalEntity->legalName, $after->legalEntity->legalName);
        $this->assertSame($before->mobilePhone, $after->mobilePhone);
    }

    public function test_missing_jmb_and_crps_do_not_block_the_rest_of_the_profile(): void
    {
        $user = $this->domesticCompany(UserType::LIMITED_LIABILITY_COMPANY, LegalEntityIdentity::FORM_DOO, 5, 71);
        $person = $this->authorizedPerson($user);
        JmbDualWrite::assignLogical($person, 'jmb', null);
        $person->save();
        $person->legalEntity->forceFill(['crps_number' => null])->save();

        $this->actingAs($user)
            ->put(route('profile.update'), $this->accountPayload($user, [
                'phone' => '+38267111071',
                'company_name' => 'Dopuna DOO',
                'jmb' => '',
                'crps_number' => '',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $saved = (new CanonicalIdentityReader)->forUser($user->fresh());
        $this->assertNull($saved->legalEntity->authorizedPerson->jmb);
        $this->assertNull($saved->legalEntity->crpsNumber);
        $this->assertSame('Dopuna DOO', $saved->legalEntity->legalName);
        $this->assertSame('+38267111071', $saved->mobilePhone);
        $this->assertNull($this->authorizedPerson($user)->jmb_encrypted);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), $this->accountPayload($user, [
                'phone' => '+38267111072',
                'company_name' => 'Druga DOO',
                'jmb' => '1234567890123',
                'crps_number' => '',
            ]))
            ->assertSessionHasErrors('jmb');

        $after = (new CanonicalIdentityReader)->forUser($user->fresh());
        $this->assertSame('Dopuna DOO', $after->legalEntity->legalName);
        $this->assertSame('+38267111071', $after->mobilePhone);
        $this->assertNull($after->legalEntity->authorizedPerson->jmb);
        $this->assertNull($after->legalEntity->crpsNumber);
    }

    public function test_passport_document_keeps_its_type_and_does_not_require_jmb(): void
    {
        $user = $this->domesticCompany(
            UserType::LIMITED_LIABILITY_COMPANY,
            LegalEntityIdentity::FORM_DOO,
            5,
            81,
            [
                'idDocumentType' => PhysicalPersonIdentity::DOCUMENT_PASSPORT,
                'jmb' => null,
                'passportNumber' => 'AB123456',
                'passportIssuingCountryCode' => 'ME',
            ],
        );
        $newCrps = $this->validCrps(5, 181);

        $html = $this->actingAs($user)->get(route('profile.edit'))->assertOk()->getContent();
        $this->assertStringNotContainsString('name="jmb"', $html);
        $this->assertStringContainsString('name="crps_number"', $html);
        $this->assertStringContainsString('Za pasoš se JMB ne unosi na ovoj stranici.', $html);

        $this->actingAs($user)
            ->put(route('profile.update'), $this->accountPayload($user, [
                'phone' => '+38267111081',
                'jmb' => '1234567890123',
                'crps_number' => $newCrps,
            ]))
            ->assertSessionHasNoErrors();

        $saved = (new CanonicalIdentityReader)->forUser($user->fresh());
        $this->assertSame(PhysicalPersonIdentity::DOCUMENT_PASSPORT, $saved->legalEntity->authorizedPerson->idDocumentType);
        $this->assertNull($saved->legalEntity->authorizedPerson->jmb);
        $this->assertSame('AB123456', $saved->legalEntity->authorizedPerson->passportNumber);
        $this->assertSame($newCrps, $saved->legalEntity->crpsNumber);
        $this->assertSame('+38267111081', $saved->mobilePhone);
        $this->assertNull($user->fresh()->getRawOriginal('jmb'));
    }

    public function test_future_youth_and_women_forms_prefill_the_new_identifiers_and_store_them_on_a_new_draft(): void
    {
        $user = $this->domesticCompany(UserType::LIMITED_LIABILITY_COMPANY, LegalEntityIdentity::FORM_DOO, 5, 91);
        $newJmb = $this->validJmb(191);
        $newCrps = $this->validCrps(5, 191);
        $this->saveIdentifiers($user, $newJmb, $newCrps);

        foreach (['omladinsko', 'zensko'] as $type) {
            $competition = $this->openCompetition($type);
            $token = $this->issueStartToken($user, $competition);
            $html = $this->actingAs($user)
                ->get(route('applications.create', ['competition' => $competition, 'start_token' => $token]))
                ->assertOk()
                ->getContent();

            $this->assertInputValue($html, 'doo_jmbg', $newJmb);
            $this->assertInputValue($html, 'crps_number', $newCrps);

            $this->actingAs($user)
                ->post(route('applications.store', $competition), [
                    'save_as_draft' => '1',
                    'start_context_token' => $token,
                    'business_plan_name' => 'Novi plan',
                    'business_area' => 'Usluge',
                    'doo_jmbg' => $newJmb,
                    'crps_number' => $newCrps,
                    'pib' => $this->validPib(91),
                ])
                ->assertSessionHasNoErrors();

            $application = Application::query()->where('user_id', $user->id)->where('competition_id', $competition->id)->firstOrFail();
            $this->assertSame('draft', $application->status);
            $this->assertSame($newCrps, $application->crps_number);
            $this->assertSame($newJmb, $application->applicantJmbgForRead());
        }
    }

    public function test_future_p1b_uses_the_updated_company_name_and_has_no_jmb_or_crps_field(): void
    {
        $user = $this->domesticCompany(UserType::LIMITED_LIABILITY_COMPANY, LegalEntityIdentity::FORM_DOO, 5, 92);
        $newJmb = $this->validJmb(192);
        $this->saveIdentifiers($user, $newJmb, $this->validCrps(5, 192), 'Nova DOO');

        $competition = $this->openCompetition(CompetitionProfileResolver::TYPE_CANONICAL);
        $this->actingAs($user)
            ->post(route('applications.canonical.start', $competition), [
                'legal_form' => CanonicalLegalForm::DRUSTVO,
            ])
            ->assertSessionHasNoErrors();

        $application = Application::query()->where('user_id', $user->id)->where('competition_id', $competition->id)->firstOrFail();
        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->firstOrFail();
        $this->assertSame('Nova DOO', $form->company_name);
        $this->assertNull($application->crps_number);
        $this->assertFalse($application->hasApplicantJmbgSnapshot());

        $page = $this->actingAs($user)
            ->get(route('applications.canonical-p1.edit', $application))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString($newJmb, $page);
        $this->assertStringNotContainsString('name="crps_number"', $page);
    }

    public function test_submitted_application_with_its_own_jmb_keeps_the_stored_identity(): void
    {
        $user = $this->domesticCompany(UserType::LIMITED_LIABILITY_COMPANY, LegalEntityIdentity::FORM_DOO, 5, 93);
        $storedJmb = $this->validJmb(193);
        $storedCrps = $this->validCrps(5, 193);
        $competition = $this->openCompetition('omladinsko');
        $application = $this->submittedCompanyApplication($user, $competition, $storedCrps, $storedJmb);

        $this->saveIdentifiers($user, $this->validJmb(293), $this->validCrps(5, 293));

        $application->refresh();
        $this->assertSame($storedJmb, $application->applicantJmbgForRead());
        $this->assertSame($storedCrps, $application->crps_number);
        $this->assertSame('submitted', $application->status);

        $html = $this->actingAs($user)
            ->get(route('applications.create', $competition).'?application_id='.$application->id)
            ->assertOk()
            ->getContent();
        $this->assertInputValue($html, 'doo_jmbg', $storedJmb);
        $this->assertInputValue($html, 'crps_number', $storedCrps);
    }

    public function test_submitted_application_without_jmb_snapshot_keeps_the_row_and_shows_the_current_profile_jmb(): void
    {
        $user = $this->domesticCompany(UserType::LIMITED_LIABILITY_COMPANY, LegalEntityIdentity::FORM_DOO, 5, 94);
        $storedCrps = $this->validCrps(5, 194);
        $competition = $this->openCompetition('omladinsko');
        $application = $this->submittedCompanyApplication($user, $competition, $storedCrps);
        $plan = BusinessPlan::create([
            'application_id' => $application->id,
            'business_idea_name' => 'Sačuvana ideja',
        ]);

        $newJmb = $this->validJmb(294);
        $this->saveIdentifiers($user, $newJmb, $this->validCrps(5, 294));

        $application->refresh();
        $plan->refresh();
        $this->assertFalse($application->hasApplicantJmbgSnapshot());
        $this->assertNull($application->getRawOriginal('applicant_jmbg'));
        $this->assertNull($application->getRawOriginal('applicant_jmbg_encrypted'));
        $this->assertSame($storedCrps, $application->crps_number);
        $this->assertNull($plan->getRawOriginal('applicant_jmbg'));
        $this->assertNull($plan->getRawOriginal('applicant_jmbg_encrypted'));
        $this->assertSame('Sačuvana ideja', $plan->business_idea_name);

        $form = $this->actingAs($user)
            ->get(route('applications.create', $competition).'?application_id='.$application->id)
            ->assertOk()
            ->getContent();
        $this->assertInputValue($form, 'doo_jmbg', $newJmb);
        $this->assertInputValue($form, 'crps_number', $storedCrps);

        $planHtml = $this->actingAs($user)
            ->get(route('applications.business-plan.create', $application))
            ->assertOk()
            ->getContent();
        $this->assertInputValue($planHtml, 'applicant_jmbg', $newJmb);
    }

    public function test_other_user_categories_keep_their_profile_fields(): void
    {
        $physical = $this->makeKorisnik(['jmb' => $this->validJmb(95)]);
        (new CanonicalIdentityWriter)->createForUser($physical, $this->flSnapshot($physical, [
            'mobilePhone' => $physical->phone,
            'person' => [
                'firstName' => $physical->first_name,
                'lastName' => $physical->last_name,
                'jmb' => $physical->jmb,
                'streetAndNumber' => 'Njegoševa 12',
                'city' => 'Kotor',
            ],
        ]));
        (new DerivedUserTypeMirror)->sync($physical, (new CanonicalIdentityReader)->forUser($physical));

        $physicalHtml = $this->actingAs($physical)->get(route('profile.edit'))->assertOk()->getContent();
        $this->assertStringContainsString('id="jmb"', $physicalHtml);
        $this->assertStringNotContainsString('id="authorized_person_jmb"', $physicalHtml);

        $entrepreneur = $this->makeKorisnik([
            'user_type' => UserType::ENTREPRENEUR,
            'jmb' => $this->validJmb(96),
            'pib' => $this->validPib(96),
        ]);
        $entrepreneurSnapshot = $this->entrepreneurSnapshot($entrepreneur);
        (new CanonicalIdentityWriter)->createForUser($entrepreneur, $entrepreneurSnapshot);
        (new DerivedUserTypeMirror)->sync($entrepreneur, $entrepreneurSnapshot);
        $entrepreneurHtml = $this->actingAs($entrepreneur)->get(route('profile.edit'))->assertOk()->getContent();
        $this->assertStringContainsString('id="jmb"', $entrepreneurHtml);
        $this->assertStringNotContainsString('id="authorized_person_jmb"', $entrepreneurHtml);

        $ngo = $this->domesticCompany(UserType::NGO_ASSOCIATION, LegalEntityIdentity::FORM_NVO_ASSOCIATION, 5, 97);
        $ngoHtml = $this->actingAs($ngo)->get(route('profile.edit'))->assertOk()->getContent();
        $this->assertStringContainsString('id="jmb"', $ngoHtml);
        $this->assertStringNotContainsString('id="authorized_person_jmb"', $ngoHtml);
        $storedNgoJmb = (new CanonicalIdentityReader)->forUser($ngo)->legalEntity->authorizedPerson->jmb;
        $this->actingAs($ngo)
            ->put(route('profile.update'), $this->accountPayload($ngo, [
                'user_type' => UserType::NGO_ASSOCIATION,
                'company_name' => 'NVO Ostaje',
                'jmb' => $this->validJmb(197),
                'crps_number' => $this->validCrps(5, 197),
            ]))
            ->assertSessionHasNoErrors();
        $savedNgo = (new CanonicalIdentityReader)->forUser($ngo->fresh());
        $this->assertSame($storedNgoJmb, $savedNgo->legalEntity->authorizedPerson->jmb);
        $this->assertSame($this->validCrps(5, 97), $savedNgo->legalEntity->crpsNumber);
        $this->assertSame('NVO Ostaje', $savedNgo->legalEntity->legalName);

        $branch = $this->makeKorisnik([
            'user_type' => UserType::LEGACY_FOREIGN_BRANCH,
            'residential_status' => null,
            'jmb' => null,
            'name' => 'Ogranak CG',
            'first_name' => null,
            'last_name' => null,
            'company_name' => 'Ogranak CG',
        ]);
        $branchSnapshot = $this->dspdSnapshot($branch);
        (new CanonicalIdentityWriter)->createForUser($branch, $branchSnapshot);
        (new DerivedUserTypeMirror)->sync($branch, $branchSnapshot);
        $branchHtml = $this->actingAs($branch)->get(route('profile.edit'))->assertOk()->getContent();
        $this->assertStringNotContainsString('id="authorized_person_jmb"', $branchHtml);
        $this->assertStringNotContainsString('id="authorized_entity_crps"', $branchHtml);

        $commission = User::factory()->create([
            'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
            'user_type' => null,
            'jmb' => null,
        ]);
        $commissionHtml = $this->actingAs($commission)->get(route('profile.edit'))->assertOk()->getContent();
        $this->assertStringNotContainsString('id="authorized_person_jmb"', $commissionHtml);
        $this->assertStringNotContainsString('id="authorized_entity_crps"', $commissionHtml);
    }

    /**
     * @param  array<string, mixed>  $personOverrides
     */
    private function domesticCompany(
        string $userType,
        string $legalForm,
        int $mark,
        int $serial,
        array $personOverrides = [],
    ): User {
        $user = $this->makeKorisnik([
            'user_type' => $userType,
            'residential_status' => null,
            'jmb' => null,
            'pib' => $this->validPib($serial),
            'company_name' => 'Društvo '.$serial,
            'name' => 'Društvo '.$serial,
            'first_name' => null,
            'last_name' => null,
        ]);
        $snapshot = $this->plSnapshot($user, array_merge([
            'jmb' => $this->validJmb($serial),
        ], $personOverrides), [
            'legalForm' => $legalForm,
            'legalName' => 'Društvo '.$serial,
            'pib' => $this->validPib($serial),
            'crpsNumber' => $this->validCrps($mark, $serial),
            'city' => 'Kotor',
            'streetAndNumber' => 'Njegoševa 12',
        ]);
        (new CanonicalIdentityWriter)->createForUser($user, $snapshot);
        (new DerivedUserTypeMirror)->sync($user, $snapshot);

        return $user->refresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function accountPayload(User $user, array $overrides = []): array
    {
        $identity = app(CurrentIdentityResolver::class)->viewFor($user);

        return array_merge([
            'first_name' => $identity->firstName,
            'last_name' => $identity->lastName,
            'email' => $user->email,
            'phone' => $identity->phone,
            'address' => $identity->address,
            'city' => $identity->city,
            'user_type' => $identity->userType,
            'company_name' => $identity->companyName,
            'pib' => $identity->pib,
        ], $overrides);
    }

    private function saveIdentifiers(User $user, string $jmb, string $crps, ?string $companyName = null): void
    {
        $this->actingAs($user)
            ->put(route('profile.update'), $this->accountPayload($user, array_filter([
                'jmb' => $jmb,
                'crps_number' => $crps,
                'company_name' => $companyName,
            ], static fn (mixed $value): bool => $value !== null)))
            ->assertSessionHasNoErrors();
    }

    private function authorizedPerson(User $user): LegalEntityAuthorizedPerson
    {
        $platformId = PlatformIdentity::query()->where('user_id', $user->id)->value('id');
        $entityId = LegalEntityIdentity::query()->where('platform_identity_id', $platformId)->value('id');

        return LegalEntityAuthorizedPerson::query()
            ->where('legal_entity_identity_id', $entityId)
            ->firstOrFail();
    }

    private function openCompetition(string $type): Competition
    {
        return Competition::create([
            'title' => 'Profil identifikatori '.$type.' '.uniqid(),
            'description' => 'Test',
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->format('Y'),
            'budget' => 10000,
            'deadline_days' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'published_at' => now()->subDay(),
        ]);
    }

    private function issueStartToken(User $user, Competition $competition): string
    {
        $response = $this->actingAs($user)->post(route('applications.start', $competition), [
            'business_stage' => 'razvoj',
        ]);
        $response->assertRedirect();
        $query = [];
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('start_token', $query);

        return $query['start_token'];
    }

    private function submittedCompanyApplication(User $user, Competition $competition, string $crps, ?string $jmb = null): Application
    {
        $application = new Application([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'applicant_type' => 'privredno_drustvo',
            'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
            'business_stage' => 'razvoj',
            'business_area' => 'Usluge',
            'business_plan_name' => 'Podneseni plan',
            'is_registered' => true,
            'crps_number' => $crps,
            'pib' => $this->validPib(93),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        if ($jmb !== null) {
            JmbDualWrite::assignLogical($application, 'applicant_jmbg', $jmb);
        }
        $application->save();

        return $application;
    }

    private function assertInputValue(string $html, string $name, string $expected): void
    {
        $this->assertMatchesRegularExpression(
            '/name="'.preg_quote($name, '/').'"[^>]*value="'.preg_quote($expected, '/').'"/s',
            $html
        );
    }
}
