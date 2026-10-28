<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\LegalEntityIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Support\UserType;
use Tests\TestCase;

class ApplicantDisplayForDecisionTest extends TestCase
{
    private function applicationWithUser(array $applicationAttrs, string $userName = 'Profil Korisnica'): Application
    {
        $user = new User(['name' => $userName]);
        $user->setRelation('identity', null);
        $application = new Application($applicationAttrs);
        $application->setRelation('user', $user);
        $application->setRelation('businessPlan', null);

        return $application;
    }

    private function withCanonicalLegalEntity(
        Application $application,
        string $legalName,
        string $legalForm = LegalEntityIdentity::FORM_DOO
    ): Application {
        $legal = new LegalEntityIdentity([
            'legal_form' => $legalForm,
            'legal_name' => $legalName,
        ]);
        $platform = new PlatformIdentity([
            'subject_type' => PlatformIdentity::SUBJECT_LEGAL_ENTITY,
        ]);
        $platform->setRelation('legalEntity', $legal);

        $user = $application->user;
        $user->setRelation('identity', $platform);
        $application->setRelation('user', $user);

        return $application;
    }

    public function test_registered_name_that_already_contains_doo_is_not_doubled(): void
    {
        $application = $this->registeredCompany([
            'founder_name' => 'Lana Lanić',
            'director_name' => 'Lana Lanić',
        ], 'Ana Anić DOO');

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame(
            'Ana Anić DOO, koga zastupa osnivačica i izvršna direktorica Lana Lanić.',
            $display
        );
        $this->assertStringNotContainsString('DOO DOO', $display);
        $this->assertStringNotContainsString('"Ana Anić DOO"', $display);
        $this->assertNull($application->decisionIdentityBlockReason());
    }

    public function test_registered_name_does_not_receive_an_automatic_legal_form_suffix(): void
    {
        $application = $this->registeredCompany([
            'doo_name' => 'Nositeljka Snapshot',
            'founder_name' => 'Lana Lanić',
            'director_name' => 'Lana Lanić',
        ], 'Firma Alfa');
        $application->setRelation('businessPlan', new BusinessPlan([
            'company_name' => 'BP Kombinovani Tekst',
        ]));

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame(
            'Firma Alfa, koga zastupa osnivačica i izvršna direktorica Lana Lanić.',
            $display
        );
        $this->assertStringNotContainsString('DOO', $display);
        $this->assertStringNotContainsString('Nositeljka Snapshot', $display);
        $this->assertStringNotContainsString('BP Kombinovani Tekst', $display);
        $this->assertStringNotContainsString('Profil Korisnica', $display);
    }

    public function test_only_founder_is_not_presented_as_executive_director_and_blocks_generation(): void
    {
        $application = $this->registeredCompany([
            'doo_name' => 'Jana Janić',
            'founder_name' => 'Jana Janić',
            'director_name' => null,
        ], 'Firma Alfa');

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame('Firma Alfa. Osnivačica: Jana Janić.', $display);
        $this->assertStringNotContainsString('direktor', mb_strtolower($display));
        $this->assertSame(
            Application::DECISION_IDENTITY_REPRESENTATION_MISSING,
            $application->decisionIdentityBlockReason()
        );
    }

    public function test_only_director_is_the_representative_and_is_not_called_founder(): void
    {
        $application = $this->registeredCompany([
            'doo_name' => 'Mina Minić',
            'founder_name' => null,
            'director_name' => 'Mina Minić',
        ], 'Firma Alfa');

        $this->assertSame(
            'Firma Alfa, koga zastupa izvršna direktorica Mina Minić.',
            $application->getApplicantDisplayForDecision()
        );
        $this->assertStringNotContainsString('Osnivačica', $application->getApplicantDisplayForDecision());
        $this->assertNull($application->decisionIdentityBlockReason());
    }

    public function test_different_founder_and_director_keep_separate_roles(): void
    {
        $application = $this->registeredCompany([
            'doo_name' => 'Treća Osoba',
            'founder_name' => 'Jana Janić',
            'director_name' => 'Mina Minić',
        ], 'Firma Alfa');

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame(
            'Firma Alfa, koga zastupa izvršna direktorica Mina Minić. Osnivačica: Jana Janić.',
            $display
        );
        $this->assertStringNotContainsString('Treća Osoba', $display);
        $this->assertNull($application->decisionIdentityBlockReason());
    }

    public function test_missing_registered_name_is_not_replaced_by_a_person_or_user_profile(): void
    {
        $application = $this->registeredCompany([
            'doo_name' => 'Jana Janić',
            'founder_name' => 'Jana Janić',
            'director_name' => 'Mina Minić',
        ], null, 'Profil Korisnica');
        $application->setRelation('businessPlan', new BusinessPlan([
            'company_name' => 'BP Ne Koristiti Kao Naziv',
        ]));

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame('Registrovani naziv nije upisan.', $display);
        $this->assertStringNotContainsString('Jana Janić', $display);
        $this->assertStringNotContainsString('Profil Korisnica', $display);
        $this->assertStringNotContainsString('BP Ne Koristiti Kao Naziv', $display);
        $this->assertStringNotContainsString('DOO', $display);
        $this->assertSame(
            Application::DECISION_IDENTITY_REGISTERED_NAME_MISSING,
            $application->decisionIdentityBlockReason()
        );
    }

    public function test_missing_or_contradictory_representation_blocks_a_new_decision(): void
    {
        $missingDirector = $this->registeredCompany([
            'founder_name' => 'Jana Janić',
            'director_name' => '   ',
        ], 'Firma Alfa');

        $this->assertSame(
            Application::DECISION_IDENTITY_REPRESENTATION_MISSING,
            $missingDirector->decisionIdentityBlockReason()
        );

        $contradiction = $this->registeredCompany([
            'registration_form' => UserType::JOINT_STOCK_COMPANY,
            'founder_name' => 'Jana Janić',
            'director_name' => 'Mina Minić',
        ], 'Firma Alfa');

        $this->assertSame(
            'Pravni oblik prijave i registrovani oblik se ne slažu.',
            $contradiction->getApplicantDisplayForDecision()
        );
        $this->assertStringNotContainsString('DOO', $contradiction->getApplicantDisplayForDecision());
        $this->assertSame(
            Application::DECISION_IDENTITY_LEGAL_FORM_CONTRADICTS,
            $contradiction->decisionIdentityBlockReason()
        );
    }

    public function test_preduzetnica_uses_the_application_name_without_business_area_or_profile(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'preduzetnica',
            'preduzetnik_name' => 'Snapshot Preduzetnica',
            'founder_name' => 'Osnivac Pogresan',
            'director_name' => 'Direktor Pogresan',
            'business_area' => 'turizam',
        ], 'Profil Korisnica');
        $application->setRelation('businessPlan', new BusinessPlan([
            'company_name' => 'BP Trgovacki Naziv',
        ]));

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame('Preduzetnica Snapshot Preduzetnica.', $display);
        $this->assertStringNotContainsString('turizam', $display);
        $this->assertStringNotContainsString('Osnivac Pogresan', $display);
        $this->assertStringNotContainsString('BP Trgovacki Naziv', $display);
        $this->assertStringNotContainsString('Profil Korisnica', $display);
        $this->assertNull($application->decisionIdentityBlockReason());

        $missing = $this->applicationWithUser([
            'applicant_type' => 'preduzetnica',
            'preduzetnik_name' => null,
            'founder_name' => 'Osnivac Legacy',
            'director_name' => 'Direktor Legacy',
        ], 'Profil Preduzetnica');

        $this->assertSame(
            'Ime preduzetnice nije upisano u prijavi.',
            $missing->getApplicantDisplayForDecision()
        );
        $this->assertStringNotContainsString('Profil Preduzetnica', $missing->getApplicantDisplayForDecision());
        $this->assertSame(
            Application::DECISION_IDENTITY_APPLICANT_NAME_MISSING,
            $missing->decisionIdentityBlockReason()
        );
    }

    public function test_physical_person_uses_only_the_application_name(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => 'Snapshot Fizicko',
        ], 'Profil Korisnica');

        $this->assertSame('Snapshot Fizicko', $application->getApplicantDisplayForDecision());
        $this->assertNull($application->decisionIdentityBlockReason());

        $missing = $this->applicationWithUser([
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => null,
        ], 'Profil Korisnica');

        $this->assertSame(
            'Ime podnositeljke nije upisano u prijavi.',
            $missing->getApplicantDisplayForDecision()
        );
        $this->assertStringNotContainsString('Profil Korisnica', $missing->getApplicantDisplayForDecision());
        $this->assertStringNotContainsString('DOO', $missing->getApplicantDisplayForDecision());
        $this->assertStringNotContainsString('Preduzetnica', $missing->getApplicantDisplayForDecision());
        $this->assertSame(
            Application::DECISION_IDENTITY_APPLICANT_NAME_MISSING,
            $missing->decisionIdentityBlockReason()
        );
    }

    public function test_other_legal_form_keeps_the_registered_name_and_states_the_form_separately(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'ostalo',
            'is_registered' => true,
            'registration_form' => UserType::JOINT_STOCK_COMPANY,
            'doo_name' => 'Nositeljka Ostalo',
            'founder_name' => 'Jana Janić',
            'director_name' => 'Mina Minić',
        ], 'Profil Korisnica');
        $application->setRelation('businessPlan', new BusinessPlan([
            'company_name' => 'BP Ignorisati',
        ]));
        $this->withCanonicalLegalEntity($application, 'Studio Epsilon', LegalEntityIdentity::FORM_AD);

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame(
            'Studio Epsilon, pravni oblik: Akcionarsko društvo, koga zastupa izvršna direktorica Mina Minić. Osnivačica: Jana Janić.',
            $display
        );
        $this->assertStringNotContainsString('DOO', $display);
        $this->assertStringNotContainsString('Nositeljka Ostalo', $display);
        $this->assertStringNotContainsString('BP Ignorisati', $display);
        $this->assertNull($application->decisionIdentityBlockReason());
    }

    public function test_unregistered_company_applicant_is_a_person_and_ignores_another_companys_legal_name(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'doo',
            'is_registered' => false,
            'doo_name' => 'Milica Milić',
            'founder_name' => null,
            'director_name' => null,
        ], 'Profil Korisnica');
        $application->setRelation('businessPlan', new BusinessPlan([
            'company_name' => 'BP Ne Koristiti Kao Naziv',
        ]));
        $this->withCanonicalLegalEntity($application, 'Tuđe Društvo DOO');

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame('Milica Milić', $display);
        $this->assertStringNotContainsString('DOO', $display);
        $this->assertStringNotContainsString('Tuđe Društvo', $display);
        $this->assertStringNotContainsString('BP Ne Koristiti Kao Naziv', $display);
        $this->assertNull($application->decisionIdentityBlockReason());
    }

    public function test_youth_company_person_is_not_rewritten_and_youth_screens_do_not_use_this_decision_text(): void
    {
        $application = $this->applicationWithUser([
            'applicant_type' => 'privredno_drustvo',
            'is_registered' => false,
            'doo_name' => 'Milica Milić',
            'company_legal_form' => 'doo',
        ], 'Profil Nalog');

        $display = $application->getApplicantDisplayForDecision();

        $this->assertSame('Milica Milić', $display);
        $this->assertStringNotContainsString('DOO', $display);
        $this->assertStringNotContainsString('Profil Nalog', $display);
        $this->assertNull($application->decisionIdentityBlockReason());

        foreach ([
            'resources/views/evaluation/youth-ranking.blade.php',
            'resources/views/evaluation/_youth_preliminary_ranking.blade.php',
            'resources/views/evaluation/_youth_equal_score_voting.blade.php',
            'resources/views/evaluation/_youth_allocation_list_confirmation.blade.php',
        ] as $relative) {
            $this->assertStringNotContainsString(
                'getApplicantDisplayForDecision',
                (string) file_get_contents(base_path($relative)),
                $relative
            );
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function registeredCompany(array $overrides, ?string $legalName, string $userName = 'Profil Korisnica'): Application
    {
        $application = $this->applicationWithUser(array_merge([
            'applicant_type' => 'doo',
            'is_registered' => true,
            'registration_form' => UserType::LIMITED_LIABILITY_COMPANY,
            'doo_name' => null,
            'founder_name' => null,
            'director_name' => null,
        ], $overrides), $userName);

        if ($legalName !== null) {
            $this->withCanonicalLegalEntity($application, $legalName);
        }

        return $application;
    }
}
