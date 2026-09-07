<?php

namespace Tests\Feature\Auth;

use App\Identity\CountryCatalog;
use App\Identity\PhoneCallingCodeCatalog;
use App\Models\User;
use App\Support\UserType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesCanonicalUsers;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use MakesCanonicalUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', $this->registrationHttpPayload('test@example.com', [
            'jmb' => $this->validJmb(1),
        ]));

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
        $this->assertSame(3, User::query()->where('email', 'test@example.com')->value('role_id'));
        $this->assertSame(UserType::PHYSICAL_PERSON, User::query()->where('email', 'test@example.com')->value('user_type'));
    }

    public function test_non_resident_physical_person_can_register(): void
    {
        $response = $this->post('/register', $this->registrationHttpPayload('mara.nerezident@example.com', [
            'first_name' => 'Mara',
            'last_name' => 'Nerezident',
            'residential_status' => 'non-resident',
            'id_document_type' => 'passport',
            'jmb' => null,
            'passport_number' => 'XY987654',
            'residence_country_code' => 'DE',
            'address' => 'Main Street 1',
            'city' => 'Berlin',
        ]));

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
        $this->assertNotNull(User::query()->where('email', 'mara.nerezident@example.com')->first());
    }

    public function test_registration_rejects_legacy_ex_non_resident_status(): void
    {
        $this->post('/register', $this->registrationHttpPayload('legacy.status@example.com', [
            'residential_status' => 'ex-non-resident',
            'jmb' => $this->validJmb(2),
        ]))->assertSessionHasErrors('residential_status');

        $this->assertGuest();
        $this->assertFalse(User::query()->where('email', 'legacy.status@example.com')->exists());
    }

    public function test_registration_form_does_not_offer_legacy_ex_non_resident_option(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();

        $this->assertStringNotContainsString('ex-non-resident', $html);
        $this->assertStringNotContainsString('Bivši nerezident', $html);
        $this->assertStringContainsString('value="resident"', $html);
        $this->assertStringContainsString('value="non-resident"', $html);
    }

    public function test_registration_phone_picker_uses_country_catalog_labels_and_preserves_calling_codes(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();

        foreach (['DE', 'MK', 'BY', 'NL', 'MM', 'US', 'RU'] as $code) {
            $label = CountryCatalog::label($code);
            $this->assertNotNull($label);
            $this->assertStringContainsString($label, $html);
        }

        $this->assertStringNotContainsString('Nemačka', $html);
        $this->assertStringNotContainsString('Severna Makedonija', $html);
        $this->assertStringNotContainsString('Belorusija', $html);
        $this->assertStringContainsString('Njemačka', $html);
        $this->assertStringContainsString('Sjeverna Makedonija', $html);
        $this->assertStringContainsString('Bjelorusija', $html);

        $expectedCallingCodes = [
            'ME' => '+382',
            'DE' => '+49',
            'MK' => '+389',
            'BY' => '+375',
            'CH' => '+41',
            'AM' => '+374',
            'NL' => '+31',
            'MM' => '+95',
            'US' => '+1',
            'CA' => '+1',
            'RU' => '+7',
            'KZ' => '+7',
            'IT' => '+39',
            'VA' => '+39',
        ];

        $entries = PhoneCallingCodeCatalog::pickerEntries();
        $byCode = [];
        foreach ($entries as $entry) {
            $byCode[$entry['country_code']] = $entry['calling_code'];
        }

        foreach ($expectedCallingCodes as $code => $callingCode) {
            $this->assertSame($callingCode, $byCode[$code] ?? null);
            $label = CountryCatalog::label($code);
            $this->assertNotNull($label);
            $this->assertStringContainsString('value="'.$callingCode.'"', $html);
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_registration_phone_rows_map_unambiguously_to_catalog_codes(): void
    {
        $entries = PhoneCallingCodeCatalog::pickerEntries();
        $this->assertCount(146, $entries);

        $pairs = [];
        foreach ($entries as $entry) {
            $this->assertTrue(CountryCatalog::isValidCode($entry['country_code']), $entry['country_code'].' must exist in CountryCatalog');
            $pairs[] = $entry['country_code']."\t".$entry['calling_code'];
        }

        $this->assertCount(146, array_unique($pairs));
    }

    public function test_registration_format_note_uses_ijekavian_umjesto(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();

        $this->assertStringContainsString('umjesto', $html);
        $this->assertStringNotContainsString('umesto', $html);
    }
}
