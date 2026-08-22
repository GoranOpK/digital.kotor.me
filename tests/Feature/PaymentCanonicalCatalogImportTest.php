<?php

namespace Tests\Feature;

use App\Models\PaymentAccount;
use App\Models\PaymentAccountAvailability;
use App\Models\PaymentCatalogAudit;
use App\Models\PaymentTransactionEvent;
use App\Models\PaymentType;
use App\Models\PaymentTypeAvailability;
use App\Models\Role;
use App\Models\User;
use App\Services\Payments\EpCanonicalCatalog;
use App\Services\Payments\EpCanonicalCatalogImporter;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesSyntheticPaymentCatalog;
use Tests\TestCase;

class PaymentCanonicalCatalogImportTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesSyntheticPaymentCatalog;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->admin = $this->userWithRole('admin', 'Platform Admin', 'ep-f11-admin@example.com');
    }

    public function test_source_has_seventeen_types_forty_one_accounts_and_excludes_bedemi(): void
    {
        EpCanonicalCatalog::assertConsistent();
        $this->assertCount(17, EpCanonicalCatalog::types());
        $this->assertCount(17, array_unique(EpCanonicalCatalog::typeCodes()));
        $this->assertCount(41, EpCanonicalCatalog::accountNumbers());
        $this->assertCount(41, array_unique(EpCanonicalCatalog::accountNumbers()));
        $this->assertNotContains(EpCanonicalCatalog::EXCLUDED_BEDEMI_ACCOUNT, EpCanonicalCatalog::accountNumbers());
        $this->assertSame([
            'prirez-porezu-na-dohodak',
            'lokalni-porezi',
            'lokalne-administrativne-takse',
            'lokalne-komunalne-takse',
            'komunalno-opremanje-zemljista',
            'koriscenje-gradjevinskog-zemljista',
            'koriscenje-puteva',
            'izgradnja-odrzavanje-lokalnih-puteva',
            'prihodi-opstinskih-organa',
            'kamate-i-kazne',
            'boravisna-taksa',
            'turisticka-taksa',
            'clanski-doprinos-turistickim-organizacijama',
            'troskovi-slobodan-pristup-informacijama',
            'taksa-akusticni-uredjaji',
            'premjestanje-vozila',
            'ekonomsko-iskoriscavanje-kulturnih-dobara',
        ], EpCanonicalCatalog::typeCodes());
    }

    public function test_import_creates_inactive_catalog_with_exact_mapping_and_availability(): void
    {
        $eventsBefore = PaymentTransactionEvent::query()->count();
        $report = app(EpCanonicalCatalogImporter::class)->import($this->admin);

        $this->assertFalse($report->hasConflicts());
        $this->assertSame(17, $report->typesCreated);
        $this->assertSame(41, $report->accountsCreated);
        $this->assertSame(0, PaymentTransactionEvent::query()->count() - $eventsBefore);

        $this->assertSame(17, PaymentType::query()->whereIn('code', EpCanonicalCatalog::typeCodes())->count());
        $this->assertSame(41, PaymentAccount::query()->whereIn('account_number', EpCanonicalCatalog::accountNumbers())->count());
        $this->assertSame(0, PaymentType::query()->whereIn('code', EpCanonicalCatalog::typeCodes())->where('is_active', true)->count());
        $this->assertSame(0, PaymentAccount::query()->whereIn('account_number', EpCanonicalCatalog::accountNumbers())->where('is_active', true)->count());
        $this->assertSame(0, PaymentAccount::query()->where('account_number', EpCanonicalCatalog::EXCLUDED_BEDEMI_ACCOUNT)->count());
        $this->assertFalse(PaymentType::query()->where('name', 'like', '%bedem%')->exists());

        foreach (EpCanonicalCatalog::types() as $typeSpec) {
            $type = PaymentType::query()->where('code', $typeSpec['code'])->firstOrFail();
            $this->assertSame($typeSpec['name'], $type->name);
            $this->assertFalse($type->is_active);
            $this->assertAvailability($type->availabilities, $typeSpec['type_set']);
            $this->assertSame(count($typeSpec['accounts']), $type->accounts()->count());

            foreach ($typeSpec['accounts'] as $accountSpec) {
                $account = PaymentAccount::query()->where('account_number', $accountSpec['number'])->firstOrFail();
                $this->assertSame($type->id, (int) $account->payment_type_id);
                $this->assertSame($accountSpec['name'], $account->name);
                $this->assertFalse($account->is_active);
                $this->assertAvailability($account->availabilities, $accountSpec['set']);
            }
        }

        $this->assertSplit('komunalno-opremanje-zemljista', [
            '530-92223906-37' => EpCanonicalCatalog::SET_LEGAL6,
            '530-92223911-22' => EpCanonicalCatalog::SET_PRED2,
            '530-92223932-56' => EpCanonicalCatalog::SET_FL2,
        ]);
        $this->assertSplit('koriscenje-gradjevinskog-zemljista', [
            '530-92223927-71' => EpCanonicalCatalog::SET_LEGAL6,
            '530-92223948-08' => EpCanonicalCatalog::SET_PRED2,
            '530-92223953-90' => EpCanonicalCatalog::SET_FL2,
        ]);
        $this->assertSplit('izgradnja-odrzavanje-lokalnih-puteva', [
            '530-92262296-06' => EpCanonicalCatalog::SET_LEGAL6,
            '530-92262303-82' => EpCanonicalCatalog::SET_PRED2,
            '530-92262319-34' => EpCanonicalCatalog::SET_FL2,
        ]);

        $this->assertAvailability(
            PaymentType::query()->where('code', 'turisticka-taksa')->firstOrFail()->availabilities,
            EpCanonicalCatalog::SET_FL2
        );
        $this->assertAvailability(
            PaymentAccount::query()->where('account_number', '530-9223206-33')->firstOrFail()->availabilities,
            EpCanonicalCatalog::SET_FL2
        );
        $this->assertAvailability(
            PaymentType::query()->where('code', 'taksa-akusticni-uredjaji')->firstOrFail()->availabilities,
            EpCanonicalCatalog::SET_BIZ6
        );
        $this->assertAvailability(
            PaymentAccount::query()->where('account_number', '530-92262335-83')->firstOrFail()->availabilities,
            EpCanonicalCatalog::SET_BIZ6
        );
        $this->assertAvailability(
            PaymentAccount::query()->where('account_number', '530-92232405-51')->firstOrFail()->availabilities,
            EpCanonicalCatalog::SET_ALL8
        );
        $this->assertAvailability(
            PaymentAccount::query()->where('account_number', '530-92232494-75')->firstOrFail()->availabilities,
            EpCanonicalCatalog::SET_BIZ6
        );
        $this->assertAvailability(
            PaymentAccount::query()->where('account_number', '530-92232517-06')->firstOrFail()->availabilities,
            EpCanonicalCatalog::SET_ALL8_MINUS_FL
        );
        foreach ([
            '530-92262320-31', '530-92262329-04', '530-92262321-28', '530-92262322-25',
            '530-92262323-22', '530-92262324-19', '530-92262326-13', '530-92262327-10',
        ] as $number) {
            $this->assertAvailability(
                PaymentAccount::query()->where('account_number', $number)->firstOrFail()->availabilities,
                EpCanonicalCatalog::SET_ALL8
            );
        }

        $this->assertGreaterThan(0, PaymentCatalogAudit::query()->count());
        $this->assertSame(0, PaymentTransactionEvent::query()->count());
        $this->assertLegalPersonsHaveNullResidential();
        $this->assertNaturalPersonsHaveResidentPairs();
    }

    public function test_second_import_is_idempotent_and_does_not_rewrite_account_numbers(): void
    {
        $importer = app(EpCanonicalCatalogImporter::class);
        $first = $importer->import($this->admin);
        $audits = PaymentCatalogAudit::query()->count();
        $accountId = PaymentAccount::query()->where('account_number', '530-9228009-77')->value('id');

        $second = $importer->import($this->admin);
        $this->assertFalse($second->hasConflicts());
        $this->assertSame(0, $second->typesCreated);
        $this->assertSame(0, $second->accountsCreated);
        $this->assertSame(0, $second->typeRulesCreated);
        $this->assertSame(0, $second->accountRulesCreated);
        $this->assertSame($audits, PaymentCatalogAudit::query()->count());
        $this->assertSame($accountId, PaymentAccount::query()->where('account_number', '530-9228009-77')->value('id'));
        $this->assertSame(17, PaymentType::query()->whereIn('code', EpCanonicalCatalog::typeCodes())->count());
        $this->assertSame(41, PaymentAccount::query()->whereIn('account_number', EpCanonicalCatalog::accountNumbers())->count());
        $this->assertSame($first->typesCreated, 17);
    }

    public function test_conflict_does_not_overwrite_name_or_rewrite_account_number(): void
    {
        PaymentType::factory()->create([
            'code' => 'prirez-porezu-na-dohodak',
            'name' => 'Wrong name',
            'is_active' => false,
        ]);
        $foreign = PaymentType::factory()->create(['code' => 'syn-foreign-type', 'is_active' => false]);
        PaymentAccount::factory()->create([
            'payment_type_id' => $foreign->id,
            'account_number' => '530-9228014-62',
            'name' => 'Held elsewhere',
            'is_active' => false,
        ]);

        $report = app(EpCanonicalCatalogImporter::class)->import($this->admin);
        $this->assertTrue($report->hasConflicts());
        $this->assertSame('Wrong name', PaymentType::query()->where('code', 'prirez-porezu-na-dohodak')->value('name'));
        $held = PaymentAccount::query()->where('account_number', '530-9228014-62')->firstOrFail();
        $this->assertSame($foreign->id, (int) $held->payment_type_id);
        $this->assertSame('Held elsewhere', $held->name);
        $this->assertNotNull(PaymentType::query()->where('code', 'lokalne-administrativne-takse')->first());
        $this->assertSame(0, PaymentTypeAvailability::query()
            ->where('payment_type_id', PaymentType::query()->where('code', 'prirez-porezu-na-dohodak')->value('id'))
            ->count());
    }

    public function test_synthetic_fixtures_remain_usable_alongside_canonical_import(): void
    {
        app(EpCanonicalCatalogImporter::class)->import($this->admin);
        $payer = $this->makeKorisnik(['email' => 'payer-f11-syn@example.com', 'jmb' => $this->validJmb(71)]);
        [$type, $account] = $this->syntheticUsablePair($payer, 'syn-f11-keep', 'SYN-F11-KEEP-00000000001');

        $this->assertSame('syn-f11-keep', $type->code);
        $this->assertTrue($type->is_active);
        $this->assertTrue($account->is_active);
        $this->assertSame(18, PaymentType::query()->count());
        $this->actingAs($this->admin)->get(route('admin.e-payments.payment-types.index'))
            ->assertOk()
            ->assertSee('Prirez porezu na dohodak fizičkih lica')
            ->assertSee('prirez-porezu-na-dohodak')
            ->assertSee('Neaktivna');
    }

    public function test_command_imports_when_actor_given_and_is_not_a_seeder(): void
    {
        $seeder = (string) file_get_contents(database_path('seeders/DatabaseSeeder.php'));
        $this->assertStringNotContainsString('EpCanonicalCatalog', $seeder);
        $this->assertStringNotContainsString('import-canonical-catalog', $seeder);

        $this->artisan('ep:import-canonical-catalog', ['--actor-id' => $this->admin->id])
            ->assertSuccessful();
        $this->assertSame(17, PaymentType::query()->whereIn('code', EpCanonicalCatalog::typeCodes())->count());
        $this->assertSame(41, PaymentAccount::query()->whereIn('account_number', EpCanonicalCatalog::accountNumbers())->count());
    }

    public function test_command_refuses_production_and_requires_actor(): void
    {
        $this->artisan('ep:import-canonical-catalog', ['--dry-run' => true])
            ->assertSuccessful();

        $this->artisan('ep:import-canonical-catalog')
            ->assertFailed();

        $this->app['env'] = 'production';
        $this->artisan('ep:import-canonical-catalog', ['--actor-id' => $this->admin->id])
            ->assertFailed();
        $this->assertSame(0, PaymentType::query()->where('code', 'prirez-porezu-na-dohodak')->count());
    }

    public function test_bankart_remains_unimplemented(): void
    {
        $hits = collect(File::allFiles(app_path()))
            ->filter(fn ($file) => str_contains($file->getFilename(), 'Bankart'))
            ->map(fn ($file) => $file->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $hits);
        $this->assertSame('fake', (string) config('payments.gateway'));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PaymentTypeAvailability|PaymentAccountAvailability>  $rules
     */
    private function assertAvailability($rules, string $set): void
    {
        $expected = [];
        foreach (EpCanonicalCatalog::availabilityRows($set) as $row) {
            $expected[EpCanonicalCatalog::rowKey($row)] = true;
        }
        $actual = [];
        foreach ($rules as $rule) {
            $actual[EpCanonicalCatalog::rowKey([
                'user_type' => (string) $rule->user_type,
                'residential_status' => $rule->residential_status,
            ])] = true;
            $this->assertTrue((bool) $rule->is_active);
        }
        ksort($expected);
        ksort($actual);
        $this->assertSame(array_keys($expected), array_keys($actual));
    }

    /**
     * @param  array<string, string>  $map
     */
    private function assertSplit(string $typeCode, array $map): void
    {
        $type = PaymentType::query()->where('code', $typeCode)->firstOrFail();
        $this->assertAvailability($type->availabilities, EpCanonicalCatalog::SET_ALL8);
        foreach ($map as $number => $set) {
            $account = PaymentAccount::query()->where('account_number', $number)->firstOrFail();
            $this->assertSame($type->id, (int) $account->payment_type_id);
            $this->assertAvailability($account->availabilities, $set);
        }
    }

    private function assertLegalPersonsHaveNullResidential(): void
    {
        foreach (UserType::canonicalLegalEntityStorageValues() as $legal) {
            $this->assertSame(0, PaymentTypeAvailability::query()
                ->where('user_type', $legal)
                ->whereNotNull('residential_status')
                ->count());
            $this->assertSame(0, PaymentAccountAvailability::query()
                ->where('user_type', $legal)
                ->whereNotNull('residential_status')
                ->count());
        }
    }

    private function assertNaturalPersonsHaveResidentPairs(): void
    {
        $type = PaymentType::query()->where('code', 'prirez-porezu-na-dohodak')->firstOrFail();
        foreach ([UserType::PHYSICAL_PERSON, UserType::ENTREPRENEUR] as $natural) {
            foreach (['resident', 'non-resident'] as $status) {
                $this->assertTrue(
                    $type->availabilities()->where('user_type', $natural)->where('residential_status', $status)->exists()
                );
            }
        }
    }

    private function userWithRole(string $role, string $name, string $email): User
    {
        $parts = explode(' ', $name, 2);

        return User::factory()->create([
            'name' => $name,
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? 'User',
            'email' => $email,
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
    }
}
