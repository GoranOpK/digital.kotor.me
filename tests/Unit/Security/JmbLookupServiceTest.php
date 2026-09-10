<?php

namespace Tests\Unit\Security;

use App\Security\JmbLookupException;
use App\Security\JmbLookupService;
use Tests\TestCase;

class JmbLookupServiceTest extends TestCase
{
    private const SYNTHETIC_JMB = '0101990009015';

    private const OTHER_JMB = '1234567890123';

    private string $lookupRawKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lookupRawKey = random_bytes(32);
        config([
            'jmb.lookup.key' => 'base64:'.base64_encode($this->lookupRawKey),
            'jmb.lookup.key_id' => 'v1',
            'jmb.encryption.key' => 'base64:'.base64_encode(random_bytes(32)),
            'jmb.encryption.key_id' => 'v1',
        ]);
        $this->app->forgetInstance(JmbLookupService::class);
    }

    public function test_null_returns_null(): void
    {
        $this->assertNull(JmbLookupService::fromConfig()->digest(null));
    }

    public function test_empty_string_returns_null(): void
    {
        $this->assertNull(JmbLookupService::fromConfig()->digest(''));
    }

    public function test_whitespace_only_returns_null(): void
    {
        $this->assertNull(JmbLookupService::fromConfig()->digest(" \t\n "));
    }

    public function test_trims_surrounding_whitespace(): void
    {
        $service = JmbLookupService::fromConfig();
        $expected = hash_hmac('sha256', self::SYNTHETIC_JMB, $this->lookupRawKey);

        $this->assertSame($expected, $service->digest('  '.self::SYNTHETIC_JMB.'  '));
    }

    public function test_valid_thirteen_digits_returns_lowercase_hex_digest(): void
    {
        $digest = JmbLookupService::fromConfig()->digest(self::SYNTHETIC_JMB);

        $this->assertIsString($digest);
        $this->assertSame(64, strlen($digest));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $digest);
        $this->assertSame(hash_hmac('sha256', self::SYNTHETIC_JMB, $this->lookupRawKey), $digest);
    }

    public function test_same_input_and_key_are_deterministic(): void
    {
        $service = JmbLookupService::fromConfig();

        $this->assertSame(
            $service->digest(self::SYNTHETIC_JMB),
            $service->digest(self::SYNTHETIC_JMB)
        );
        $this->assertSame(
            $service->digest(self::SYNTHETIC_JMB),
            JmbLookupService::fromConfig()->digest(self::SYNTHETIC_JMB)
        );
    }

    public function test_different_input_produces_different_digest(): void
    {
        $service = JmbLookupService::fromConfig();

        $this->assertNotSame(
            $service->digest(self::SYNTHETIC_JMB),
            $service->digest(self::OTHER_JMB)
        );
    }

    public function test_checksum_is_not_enforced(): void
    {
        $digest = JmbLookupService::fromConfig()->digest(self::OTHER_JMB);

        $this->assertIsString($digest);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $digest);
    }

    public function test_invalid_short_value_fails_closed(): void
    {
        $this->assertInvalidJmbDoesNotLeak('123456789012');
    }

    public function test_invalid_long_value_fails_closed(): void
    {
        $this->assertInvalidJmbDoesNotLeak('12345678901234');
    }

    public function test_nondigit_value_fails_closed(): void
    {
        $this->assertInvalidJmbDoesNotLeak('010199000901a');
    }

    public function test_missing_key_fails_for_non_empty_jmb_but_not_for_null(): void
    {
        config(['jmb.lookup.key' => '']);
        $this->app->forgetInstance(JmbLookupService::class);
        $service = JmbLookupService::fromConfig();

        $this->assertNull($service->digest(null));
        $this->assertNull($service->digest(''));

        try {
            $service->digest(self::SYNTHETIC_JMB);
            $this->fail('Missing JMB lookup key must fail for a non-empty JMB.');
        } catch (JmbLookupException $e) {
            $this->assertStringContainsString('JMB lookup key is missing', $e->getMessage());
            $this->assertStringContainsString('JMB_LOOKUP_KEY', $e->getMessage());
            $this->assertStringContainsString('Do not use APP_KEY', $e->getMessage());
            $this->assertStringContainsString('JMB_ENCRYPTION_KEY', $e->getMessage());
            $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $e->getMessage());
            $this->assertStringNotContainsString(base64_encode($this->lookupRawKey), $e->getMessage());
        }
    }

    public function test_malformed_base64_fails_closed(): void
    {
        config(['jmb.lookup.key' => 'base64:@@@not-base64@@@']);
        $this->app->forgetInstance(JmbLookupService::class);

        try {
            JmbLookupService::fromConfig()->digest(self::SYNTHETIC_JMB);
            $this->fail('Malformed lookup key must fail.');
        } catch (JmbLookupException $e) {
            $this->assertStringContainsString('JMB lookup key is invalid', $e->getMessage());
            $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $e->getMessage());
            $this->assertStringNotContainsString('@@@not-base64@@@', $e->getMessage());
        }
    }

    public function test_decoded_key_not_32_bytes_fails_closed(): void
    {
        config(['jmb.lookup.key' => 'base64:'.base64_encode('too-short')]);
        $this->app->forgetInstance(JmbLookupService::class);

        try {
            JmbLookupService::fromConfig()->digest(self::SYNTHETIC_JMB);
            $this->fail('Short lookup key must fail.');
        } catch (JmbLookupException $e) {
            $this->assertStringContainsString('JMB lookup key is invalid', $e->getMessage());
            $this->assertStringContainsString('32 bytes', $e->getMessage());
            $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $e->getMessage());
            $this->assertStringNotContainsString('too-short', $e->getMessage());
        }
    }

    public function test_service_does_not_reuse_jmb_encryption_key(): void
    {
        $encryptionRawKey = random_bytes(32);
        config([
            'jmb.encryption.key' => 'base64:'.base64_encode($encryptionRawKey),
            'jmb.lookup.key' => 'base64:'.base64_encode($this->lookupRawKey),
        ]);
        $this->app->forgetInstance(JmbLookupService::class);

        $digest = JmbLookupService::fromConfig()->digest(self::SYNTHETIC_JMB);

        $this->assertSame(hash_hmac('sha256', self::SYNTHETIC_JMB, $this->lookupRawKey), $digest);
        $this->assertNotSame(hash_hmac('sha256', self::SYNTHETIC_JMB, $encryptionRawKey), $digest);

        config(['jmb.lookup.key' => '']);
        $this->app->forgetInstance(JmbLookupService::class);

        $this->expectException(JmbLookupException::class);
        $this->expectExceptionMessage('JMB lookup key is missing');
        JmbLookupService::fromConfig()->digest(self::SYNTHETIC_JMB);
    }

    public function test_key_id_is_available_but_not_stored_in_digest(): void
    {
        config(['jmb.lookup.key_id' => 'v2']);
        $this->app->forgetInstance(JmbLookupService::class);
        $service = JmbLookupService::fromConfig();

        $this->assertSame('v2', $service->keyId());
        $digest = $service->digest(self::SYNTHETIC_JMB);
        $this->assertIsString($digest);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $digest);
        $this->assertStringNotContainsString('v2', $digest);
        $this->assertStringNotContainsString('v1', $digest);
        $this->assertFalse(str_starts_with($digest, 'v2'));
        $this->assertSame(JmbLookupService::DIGEST_LENGTH, strlen($digest));
    }

    public function test_container_resolves_without_lookup_key_and_does_not_log_secrets(): void
    {
        config(['jmb.lookup.key' => '']);
        $this->app->forgetInstance(JmbLookupService::class);

        $service = app(JmbLookupService::class);
        $this->assertInstanceOf(JmbLookupService::class, $service);
        $this->assertNull($service->digest(null));

        $source = (string) file_get_contents(base_path('app/Security/JmbLookupService.php'));
        $this->assertDoesNotMatchRegularExpression(
            '/\bLog::|\\\\Log::|->info\(|->error\(|->warning\(|->debug\(|->notice\(/',
            $source
        );
    }

    private function assertInvalidJmbDoesNotLeak(string $value): void
    {
        try {
            JmbLookupService::fromConfig()->digest($value);
            $this->fail('Invalid JMB must fail closed: '.$value);
        } catch (JmbLookupException $e) {
            $this->assertStringContainsString('JMB lookup value is invalid', $e->getMessage());
            $this->assertStringNotContainsString($value, $e->getMessage());
            $this->assertStringNotContainsString(trim($value), $e->getMessage());
        }
    }
}
