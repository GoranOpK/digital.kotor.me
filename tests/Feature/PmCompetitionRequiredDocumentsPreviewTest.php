<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Services\KnApplicationStartContextFactory;
use App\Support\KnOmladinskoDocumentPackage;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class PmCompetitionRequiredDocumentsPreviewTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    private const NOTICE = 'Obrasci za prijavu određuju se prema tipu podnosioca i fazi razvoja biznisa.';

    private const M1A = 'Prijava na konkurs za podršku preduzetništvu mladih (obrazac M1a)';

    private const M1B = 'Prijava na konkurs za podršku preduzetništvu mladih (obrazac M1b)';

    private const M2 = 'Popunjena forma za biznis plan (obrazac M2)';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_supported_physical_person_sees_m1a_m2_and_package_attachments(): void
    {
        $html = $this->page($this->makeKorisnik(['jmb' => $this->validJmb(801)]), $this->youth());

        $this->assertStringContainsString('<li>'.self::M1A.'</li>', $html);
        $this->assertStringContainsString('<li>'.self::M2.'</li>', $html);
        $this->assertStringContainsString('<li>Ovjerena kopija lične karte</li>', $html);
        $this->assertStringNotContainsString('<li>'.self::M1B.'</li>', $html);
        $this->assertNoGenericYouthFallback($html);
        $this->assertStringNotContainsString(self::NOTICE, $html);
    }

    public function test_unregistered_physical_person_planning_a_company_previews_youth_package_three(): void
    {
        $html = $this->page($this->makeKorisnik(['jmb' => $this->validJmb(806)]), $this->youth());
        $package = KnOmladinskoDocumentPackage::resolve('privredno_drustvo', 'započinjanje', false);
        $items = $this->youthPreviewItems($html, 'privredno_drustvo', 'započinjanje');

        $this->assertStringContainsString('Planiram osnivanje privrednog društva', $html);
        $this->assertSame(3, $package->number);
        $this->assertTrue($package->isM1b());
        $this->assertSame($package->previewItems(), $items);
        $this->assertSame(self::M1B, $items[0]);
        $this->assertSame(self::M2, $items[1]);
        $this->assertContains('Ovjerena kopija lične karte nosioca biznisa', $items);
        $this->assertContains('Predračuni za planiranu nabavku', $items);
        $this->assertNotContains('Rješenje o upisu u CRPS (ukoliko ima registrovanu djelatnost)', $items);
        $this->assertNotSame(
            KnOmladinskoDocumentPackage::resolve('preduzetnik', 'razvoj', true)->previewItems(),
            $items
        );
        $this->assertNoGenericYouthFallback($html);
        $this->assertStringNotContainsString(self::NOTICE, $html);
    }

    public function test_supported_registered_entrepreneur_sees_m1a_m2_and_package_attachments(): void
    {
        $user = $this->makeKorisnik([
            'jmb' => $this->validJmb(802),
            'user_type' => UserType::ENTREPRENEUR,
            'pib' => $this->validPib(802),
            'company_name' => 'Mladi preduzetnik',
            'residential_status' => null,
        ]);

        $html = $this->page($user, $this->youth());

        $this->assertStringContainsString('<li>'.self::M1A.'</li>', $html);
        $this->assertStringContainsString('<li>'.self::M2.'</li>', $html);
        $this->assertStringContainsString('<li>Ovjerena kopija lične karte</li>', $html);
        $this->assertStringContainsString('Rješenje o upisu u CRPS', $html);
        $this->assertStringNotContainsString('<li>'.self::M1B.'</li>', $html);
        $this->assertNoGenericYouthFallback($html);
        $this->assertStringNotContainsString(self::NOTICE, $html);
    }

    public function test_supported_registered_company_sees_m1b_m2_and_package_attachments(): void
    {
        $user = $this->makeKorisnik([
            'jmb' => $this->validJmb(803),
            'user_type' => UserType::LIMITED_LIABILITY_COMPANY,
            'pib' => $this->validPib(803),
            'company_name' => 'Mladi DOO',
            'residential_status' => null,
        ]);

        $html = $this->page($user, $this->youth());

        $this->assertStringContainsString('<li>'.self::M1B.'</li>', $html);
        $this->assertStringContainsString('<li>'.self::M2.'</li>', $html);
        $this->assertStringContainsString('<li>Ovjerena kopija lične karte nosioca biznisa</li>', $html);
        $this->assertStringNotContainsString('<li>'.self::M1A.'</li>', $html);
        $this->assertNoGenericYouthFallback($html);
        $this->assertStringNotContainsString(self::NOTICE, $html);
    }

    public function test_superadmin_without_a_youth_package_sees_the_notice_and_keeps_the_identity_message(): void
    {
        $superadmin = User::factory()->create([
            'role_id' => Role::where('name', 'superadmin')->firstOrFail()->id,
            'activation_status' => 'active',
            'user_type' => null,
            'email_verified_at' => now(),
        ]);

        $html = $this->page($superadmin, $this->youth());

        $this->assertViewerWithoutPackage($html);
        $this->assertStringContainsString(
            KnApplicationStartContextFactory::UNSUPPORTED_OMLADINSKO_IDENTITY_MESSAGE,
            $html
        );
    }

    public function test_guest_is_sent_to_login_and_does_not_see_generic_form_titles(): void
    {
        $response = $this->get(route('competitions.show', $this->youth()));

        $response->assertRedirect(route('login'));
        $html = $response->getContent();
        $this->assertStringNotContainsString('Obrazac 1a ili 1b', $html);
        $this->assertStringNotContainsString('Obrazac 2', $html);
        $this->assertStringNotContainsString('obrazac 1a', $html);
        $this->assertStringNotContainsString('obrazac 2', $html);
    }

    public function test_unsupported_identity_sees_the_notice_without_a_package_or_generic_titles(): void
    {
        $user = $this->makeKorisnik([
            'user_type' => UserType::NGO_ASSOCIATION,
            'residential_status' => null,
            'company_name' => 'NVO Mladi',
            'pib' => $this->validPib(804),
            'jmb' => $this->validJmb(804),
        ]);

        $html = $this->page($user, $this->youth());

        $this->assertViewerWithoutPackage($html);
        $this->assertStringContainsString(
            KnApplicationStartContextFactory::UNSUPPORTED_OMLADINSKO_IDENTITY_MESSAGE,
            $html
        );
        $this->assertStringNotContainsString('Prijavi se na konkurs', $html);
        $this->assertNotSame(
            self::NOTICE,
            KnApplicationStartContextFactory::UNSUPPORTED_OMLADINSKO_IDENTITY_MESSAGE
        );
    }

    public function test_zensko_preview_keeps_existing_form_titles(): void
    {
        $html = $this->page($this->makeKorisnik(['jmb' => $this->validJmb(805)]), $this->competition('zensko'));

        $this->assertStringContainsString('Obrazac 1a – preduzetnica', $html);
        $this->assertStringContainsString('Popunjena forma za biznis plan (obrazac 2 — Forma za biznis plan)', $html);
        $this->assertStringContainsString('Prilikom prijave na konkurs, potrebno je priložiti sledeće dokumente:', $html);
        $this->assertStringNotContainsString(self::NOTICE, $html);
        $this->assertStringNotContainsString(self::M1A, $html);
        $this->assertStringNotContainsString(self::M2, $html);
        $this->assertStringNotContainsString(
            KnApplicationStartContextFactory::UNSUPPORTED_OMLADINSKO_IDENTITY_MESSAGE,
            $html
        );
    }

    public function test_mmsp_preview_keeps_canonical_forms(): void
    {
        $superadmin = User::factory()->create([
            'role_id' => Role::where('name', 'superadmin')->firstOrFail()->id,
            'activation_status' => 'active',
            'user_type' => null,
            'email_verified_at' => now(),
        ]);
        $competition = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, [
            'status' => 'draft',
            'is_test_preview' => true,
            'published_at' => null,
        ]);

        $html = $this->page($superadmin, $competition);

        $this->assertStringContainsString('Preduzetnik — Obrazac P1a', $html);
        $this->assertStringContainsString('Privredno društvo (DOO) — Obrazac P1b', $html);
        $this->assertStringNotContainsString(self::NOTICE, $html);
        $this->assertStringNotContainsString('Obrazac 1a ili 1b', $html);
        $this->assertStringNotContainsString(self::M1A, $html);
        $this->assertStringNotContainsString(self::M2, $html);
    }

    private function page(User $user, Competition $competition): string
    {
        return $this->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->getContent();
    }

    private function assertViewerWithoutPackage(string $html): void
    {
        $this->assertStringContainsString('id="pm-documents-notice"', $html);
        $this->assertStringContainsString(self::NOTICE, $html);
        $this->assertStringNotContainsString('Prilikom prijave na konkurs, potrebno je priložiti sledeće dokumente:', $html);
        $this->assertStringNotContainsString('<li>'.self::M1A.'</li>', $html);
        $this->assertStringNotContainsString('<li>'.self::M1B.'</li>', $html);
        $this->assertStringNotContainsString('<li>'.self::M2.'</li>', $html);
        $this->assertNoGenericYouthFallback($html);
    }

    /**
     * @return list<string>
     */
    private function youthPreviewItems(string $html, string $type, string $stage): array
    {
        $marker = 'const omladinskoPreviewMaps = ';
        $start = strpos($html, $marker);
        $this->assertNotFalse($start);
        $tail = substr($html, $start + strlen($marker));
        $end = strpos($tail, ';');
        $this->assertNotFalse($end);
        $maps = json_decode(substr($tail, 0, $end), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($maps[$type][$stage]['items'] ?? null);

        return $maps[$type][$stage]['items'];
    }

    private function assertNoGenericYouthFallback(string $html): void
    {
        foreach ([
            'Obrazac 1a ili 1b',
            'Obrazac 1a – preduzetnica',
            'Obrazac 1b',
            'obrazac 1a',
            'obrazac 1b',
            'Obrazac 2',
            'obrazac 2',
        ] as $label) {
            $this->assertStringNotContainsString($label, $html);
        }
    }

    private function youth(): Competition
    {
        return $this->competition('omladinsko');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function competition(string $type, array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'title' => 'KN prikaz '.$type,
            'description' => 'Opis',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'deadline_days' => 20,
            'published_at' => now()->subDays(2),
        ], $overrides));
    }
}
