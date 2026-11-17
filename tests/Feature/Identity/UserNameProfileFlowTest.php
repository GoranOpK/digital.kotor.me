<?php

namespace Tests\Feature\Identity;

use App\Identity\CanonicalIdentityWriter;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class UserNameProfileFlowTest extends TestCase
{
    use DatabaseTransactions, MakesCanonicalUsers, MakesIdentitySnapshots;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config(['identity.canonical_read' => true, 'identity.canonical_write' => true]);
    }

    private function user(): User
    {
        $user = $this->makeKorisnik(['first_name' => 'Didije', 'last_name' => 'Drogba', 'name' => 'Didije Drogba']);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['firstName' => 'Didije', 'lastName' => 'Drogba'],
        ]));
        return $user;
    }

    public function test_profile_save_returns_to_profile_with_success_and_fresh_names_in_both_sections_and_navigation(): void
    {
        $user = $this->user();
        $this->actingAs($user)->get(route('profile.edit'))->assertOk()
            ->assertSee('name="name_context" value="profile"', false);
        $this->put(route('profile.name.update'), [
            'name_context' => 'profile', 'first_name' => 'Marko', 'last_name' => "Markovi\u{0107}",
        ])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors()
            ->assertSessionHas('name_updated', "Ime i prezime su uspje\u{0161}no izmijenjeni.");
        $this->assertSame("Marko Markovi\u{0107}", $user->fresh()->name);
        $response = $this->actingAs($user->fresh())->get(route('profile.edit'))->assertOk();
        $response->assertSee("Ime i prezime su uspje\u{0161}no izmijenjeni.")->assertSee('class="alert alert-success" role="status"', false);
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        foreach (['account_first_name' => 'Marko', 'first_name' => 'Marko', 'account_last_name' => "Markovi\u{0107}", 'last_name' => "Markovi\u{0107}"] as $id => $value) {
            $this->assertSame($value, $xpath->query('//input[@id="'.$id.'"]')->item(0)?->getAttribute('value'));
        }
        $this->assertStringContainsString("Marko Markovi\u{0107}", $xpath->query('//nav')->item(0)->textContent);
    }

    public function test_profile_validation_failure_stays_on_profile_and_keeps_input_and_inline_error(): void
    {
        $user = $this->user();
        $this->actingAs($user)->from('https://example.invalid/untrusted')->put(route('profile.name.update'), [
            'name_context' => 'profile', 'first_name' => 'Novo ime', 'last_name' => '',
        ])->assertRedirect(route('profile.edit'))->assertSessionHasErrors('last_name')
            ->assertSessionHasInput('first_name', 'Novo ime')->assertSessionMissing('name_updated');
        $this->get(route('profile.edit'))->assertOk()->assertSee('value="Novo ime"', false)
            ->assertSee('id="account_last_name_error"', false);
        $this->assertSame('Didije Drogba', $user->fresh()->name);
    }

    public function test_service_rejection_returns_to_profile_without_false_success_or_lost_input(): void
    {
        $user = $this->user();
        config(['identity.canonical_write' => false]);
        $this->actingAs($user)->from('https://example.invalid/untrusted')->put(route('profile.name.update'), [
            'name_context' => 'profile', 'first_name' => 'Marko', 'last_name' => 'Markovic',
        ])->assertRedirect(route('profile.edit'))->assertSessionHasErrors('first_name')
            ->assertSessionHasInput('first_name', 'Marko')->assertSessionMissing('name_updated')->assertSessionMissing('success');
        $this->assertSame('Didije Drogba', $user->fresh()->name);
    }

    public function test_unreadable_identity_returns_to_profile_with_error(): void
    {
        $user = $this->makeKorisnik(); // Subject evidence without a canonical graph is not ACCOUNT_ONLY.
        $this->actingAs($user)->put(route('profile.name.update'), [
            'name_context' => 'profile', 'first_name' => 'Novo', 'last_name' => 'Prezime',
        ])->assertRedirect(route('profile.edit'))->assertSessionHasErrors('first_name')
            ->assertSessionHasInput('first_name', 'Novo')->assertSessionMissing('name_updated');
    }

    public static function standaloneContexts(): array
    {
        return [[null], ['name'], ['https://example.invalid/'], ['//example.invalid/']];
    }

    #[DataProvider('standaloneContexts')]
    public function test_unverified_standalone_flow_and_untrusted_redirects_keep_the_existing_name_screen(?string $context): void
    {
        $user = $this->user();
        $user->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($user)->get(route('profile.name.edit'))->assertOk()
            ->assertSee('name="name_context" value="name"', false);
        $payload = ['first_name' => 'Marko', 'last_name' => 'Markovic', 'redirect' => 'https://example.invalid/'];
        if ($context !== null) $payload['name_context'] = $context;
        $this->from('https://example.invalid/untrusted')->put(route('profile.name.update'), $payload)
            ->assertRedirect(route('profile.name.edit'))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->get(route('profile.name.edit'))->assertOk();
        $this->get(route('profile.edit'))->assertRedirect(route('verification.notice'));
    }

    public function test_standalone_validation_errors_also_return_to_the_name_screen(): void
    {
        $this->actingAs($this->user())->from('https://example.invalid/untrusted')->put(route('profile.name.update'), [
            'first_name' => 'Novo', 'last_name' => '', 'name_context' => 'name',
        ])->assertRedirect(route('profile.name.edit'))->assertSessionHasErrors('last_name')->assertSessionHasInput('first_name', 'Novo');
    }
}
