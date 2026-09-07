<?php

namespace Tests\Unit\Identity;

use App\Identity\PhoneCallingCodeCatalog;
use App\Identity\Runtime\ExistingSubjectIdentityReturnTo;
use Illuminate\Http\Request;
use Tests\TestCase;

class ExistingSubjectIdentityReturnToAndPhoneTest extends TestCase
{
    public function test_return_to_allows_competition_paths_and_rejects_open_redirects(): void
    {
        $returnTo = new ExistingSubjectIdentityReturnTo;

        $this->assertTrue($returnTo->isSafe('/competitions/12/apply'));
        $this->assertTrue($returnTo->isSafe('/competitions/12/apply?applicant_type=doo'));
        $this->assertFalse($returnTo->isSafe('//evil.example'));
        $this->assertFalse($returnTo->isSafe('https://evil.example'));
        $this->assertFalse($returnTo->isSafe('/login'));
        $this->assertFalse($returnTo->isSafe('/register'));
        $this->assertFalse($returnTo->isSafe('/admin'));
        $this->assertFalse($returnTo->isSafe('/admin/users'));
        $this->assertFalse($returnTo->isSafe('/evaluation'));
        $this->assertFalse($returnTo->isSafe('/evaluation/1'));
        $this->assertFalse($returnTo->isSafe('/competitions/../login'));
        $this->assertFalse($returnTo->isSafe('/dashboard'));
    }

    public function test_remember_from_request_stores_only_safe_competition_path(): void
    {
        $returnTo = new ExistingSubjectIdentityReturnTo;
        $request = Request::create('/competitions/9/apply', 'GET', ['applicant_type' => 'doo']);
        $returnTo->rememberFromRequest($request);

        $this->assertSame('/competitions/9/apply?applicant_type=doo', session(ExistingSubjectIdentityReturnTo::SESSION_KEY));
        $this->assertSame('/competitions/9/apply?applicant_type=doo', $returnTo->consume());
        $this->assertNull(session(ExistingSubjectIdentityReturnTo::SESSION_KEY));
    }

    public function test_phone_suggestion_requires_unique_plus_prefix_and_never_defaults_me(): void
    {
        $this->assertSame(
            ['calling_code' => '+382', 'national' => '67000001'],
            PhoneCallingCodeCatalog::suggestionFromStored('+38267000001')
        );
        $this->assertNull(PhoneCallingCodeCatalog::suggestionFromStored('067000001'));
        $this->assertNull(PhoneCallingCodeCatalog::suggestionFromStored('67000001'));
        $this->assertNull(PhoneCallingCodeCatalog::suggestionFromStored(null));
        $this->assertSame('+38164123456', PhoneCallingCodeCatalog::compose('+381', '64123456'));
        $this->assertSame('+382067111', PhoneCallingCodeCatalog::compose('+382', '067111'));

        $labels = array_column(PhoneCallingCodeCatalog::pickerEntries(), 'label');
        $this->assertContains('Crna Gora', $labels);
        $this->assertNotContains('ME', $labels);
    }
}
