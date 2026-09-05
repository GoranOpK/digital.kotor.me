<?php

namespace Tests\Feature\Identity;

use App\Identity\CanonicalIdentityReadException;
use App\Identity\CanonicalIdentityReader;
use App\Identity\CanonicalIdentityWriter;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class CanonicalIdentityReaderTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    private CanonicalIdentityWriter $writer;

    private CanonicalIdentityReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->assertSame(false, config('identity.canonical_read'));
        $this->writer = new CanonicalIdentityWriter;
        $this->reader = new CanonicalIdentityReader;
    }

    public function test_reads_physical_person_graph(): void
    {
        $user = $this->makeKorisnik();
        $this->writer->createForUser($user, $this->flSnapshot($user));

        $snapshot = $this->reader->forUser($user);

        $this->assertTrue($snapshot->isRegisteredSubject);
        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $snapshot->subjectType);
        $this->assertSame('+38267000001', $snapshot->mobilePhone);
        $this->assertSame('Njegoševa 12', $snapshot->streetAndNumber);
        $this->assertSame('Podgorica', $snapshot->city);
        $this->assertSame('Ana', $snapshot->physicalPerson?->firstName);
        $this->assertSame('0000000000000', $snapshot->physicalPerson?->jmb);
        $this->assertFalse($snapshot->physicalPerson?->isEntrepreneur);
        $this->assertNull($snapshot->legalEntity);
        $this->assertNull($snapshot->foreignBranch);
        $this->assertNull($snapshot->legacyFacts);
    }

    public function test_reads_entrepreneur_context(): void
    {
        $user = $this->makeKorisnik();
        $this->writer->createForUser($user, $this->entrepreneurSnapshot($user));

        $snapshot = $this->reader->forUser($user);

        $this->assertSame(PlatformIdentity::SUBJECT_PHYSICAL_PERSON, $snapshot->subjectType);
        $this->assertTrue($snapshot->physicalPerson?->isEntrepreneur);
        $this->assertSame('Radnja Ana', $snapshot->physicalPerson?->entrepreneurBusinessName);
        $this->assertSame('12345672', $snapshot->physicalPerson?->pib);
        $this->assertSame('10000001', $snapshot->physicalPerson?->crpsNumber);
    }

    public function test_reads_legal_entity_and_authorized_person(): void
    {
        $user = $this->makeKorisnik();
        $this->writer->createForUser($user, $this->plSnapshot($user));

        $snapshot = $this->reader->forUser($user);

        $this->assertSame(PlatformIdentity::SUBJECT_LEGAL_ENTITY, $snapshot->subjectType);
        $this->assertSame('+38267000002', $snapshot->mobilePhone);
        $this->assertSame('Slobode 1', $snapshot->streetAndNumber);
        $this->assertSame('Podgorica', $snapshot->city);
        $this->assertSame(LegalEntityIdentity::FORM_DOO, $snapshot->legalEntity?->legalForm);
        $this->assertSame('Primjer DOO', $snapshot->legalEntity?->legalName);
        $this->assertSame('Marko', $snapshot->legalEntity?->authorizedPerson?->firstName);
        $this->assertSame('0000000000000', $snapshot->legalEntity?->authorizedPerson?->jmb);
        $this->assertNull($snapshot->physicalPerson);
    }

    public function test_reads_foreign_branch_and_representative(): void
    {
        $user = $this->makeKorisnik();
        $this->writer->createForUser($user, $this->dspdSnapshot($user));

        $snapshot = $this->reader->forUser($user);

        $this->assertSame(PlatformIdentity::SUBJECT_FOREIGN_BRANCH, $snapshot->subjectType);
        $this->assertSame('+38267000003', $snapshot->mobilePhone);
        $this->assertSame('Bulevar 8', $snapshot->streetAndNumber);
        $this->assertSame('Foreign Co', $snapshot->foreignBranch?->foreignCompanyName);
        $this->assertSame('Ogranak CG', $snapshot->foreignBranch?->branchNameInMontenegro);
        $this->assertSame('Jelena', $snapshot->foreignBranch?->representative?->firstName);
        $this->assertSame('IT', $snapshot->foreignBranch?->representative?->passportIssuingCountryCode);
        $this->assertNull($snapshot->physicalPerson);
        $this->assertNull($snapshot->legalEntity);
    }

    public function test_does_not_merge_cross_branch_inconsistent_storage(): void
    {
        $user = $this->makeKorisnik();
        $this->writer->createForUser($user, $this->flSnapshot($user));
        $platform = PlatformIdentity::query()->where('user_id', $user->id)->first();

        LegalEntityIdentity::query()->create([
            'platform_identity_id' => $platform->id,
            'legal_form' => LegalEntityIdentity::FORM_DOO,
            'legal_name' => 'Invalid Extra',
            'street_and_number' => 'X 1',
            'city' => 'Podgorica',
        ]);

        $this->expectException(CanonicalIdentityReadException::class);
        $this->reader->forUser($user->fresh());
    }

    public function test_does_not_fall_back_to_legacy_when_canonical_is_missing(): void
    {
        $user = $this->makeKorisnik();

        $this->expectException(CanonicalIdentityReadException::class);
        $this->reader->forUser($user);
    }
}
