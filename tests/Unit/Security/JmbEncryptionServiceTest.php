<?php

namespace Tests\Unit\Security;

use App\Security\JmbEncryptionException;
use App\Security\JmbEncryptionService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class JmbEncryptionServiceTest extends TestCase
{
    private const SYNTHETIC_JMB = '0101990009015';

    private string $jmbRawKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jmbRawKey = random_bytes(32);
        config([
            'jmb.encryption.key' => 'base64:'.base64_encode($this->jmbRawKey),
            'jmb.encryption.key_id' => 'v1',
            'jmb.encryption.previous_keys' => '',
            'jmb.encryption.cipher' => JmbEncryptionService::CIPHER,
        ]);
        $this->app->forgetInstance(JmbEncryptionService::class);
    }

    public function test_round_trip_jmb_works(): void
    {
        $service = JmbEncryptionService::fromConfig();
        $ciphertext = $service->encrypt(self::SYNTHETIC_JMB);

        $this->assertIsString($ciphertext);
        $this->assertSame(self::SYNTHETIC_JMB, $service->decrypt($ciphertext));
        $this->assertSame(
            self::SYNTHETIC_JMB,
            app(JmbEncryptionService::class)->decrypt($ciphertext)
        );
    }

    public function test_same_plaintext_produces_nondeterministic_ciphertext(): void
    {
        $service = JmbEncryptionService::fromConfig();
        $first = $service->encrypt(self::SYNTHETIC_JMB);
        $second = $service->encrypt(self::SYNTHETIC_JMB);

        $this->assertNotSame($first, $second);
        $this->assertSame(self::SYNTHETIC_JMB, $service->decrypt($first));
        $this->assertSame(self::SYNTHETIC_JMB, $service->decrypt($second));
    }

    public function test_ciphertext_does_not_contain_plaintext_jmb(): void
    {
        $ciphertext = JmbEncryptionService::fromConfig()->encrypt(self::SYNTHETIC_JMB);

        $this->assertIsString($ciphertext);
        $this->assertStringStartsWith('jmb:v1:', $ciphertext);
        $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $ciphertext);
        $this->assertStringNotContainsString(base64_encode(self::SYNTHETIC_JMB), $ciphertext);
    }

    public function test_null_and_empty_string_stay_null(): void
    {
        $service = JmbEncryptionService::fromConfig();

        $this->assertNull($service->encrypt(null));
        $this->assertNull($service->encrypt(''));
        $this->assertNull($service->decrypt(null));
        $this->assertNull($service->decrypt(''));
    }

    public function test_malformed_and_tampered_ciphertext_fails_safely(): void
    {
        $service = JmbEncryptionService::fromConfig();
        $valid = $service->encrypt(self::SYNTHETIC_JMB);
        $this->assertIsString($valid);

        $tampered = substr($valid, 0, -4).'XXXX';

        $invalid = [
            self::SYNTHETIC_JMB,
            'not-ciphertext',
            'jmb:v1:',
            'jmb::payload',
            'v1:'.$valid,
            $tampered,
            'jmb:v999:'.explode(':', $valid, 3)[2],
        ];

        foreach ($invalid as $ciphertext) {
            try {
                $result = $service->decrypt($ciphertext);
            } catch (JmbEncryptionException $e) {
                $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $e->getMessage());
                $this->assertStringNotContainsString((string) $ciphertext, $e->getMessage());
                continue;
            }

            $this->fail('Decrypt must not succeed or silently return a value for: '.$ciphertext.'. Got: '.var_export($result, true));
        }
    }

    public function test_missing_encryption_key_fails_clearly(): void
    {
        config(['jmb.encryption.key' => '']);
        $this->app->forgetInstance(JmbEncryptionService::class);

        try {
            JmbEncryptionService::fromConfig();
            $this->fail('Missing JMB key must fail.');
        } catch (JmbEncryptionException $e) {
            $this->assertStringContainsString('JMB encryption key is missing', $e->getMessage());
            $this->assertStringContainsString('JMB_ENCRYPTION_KEY', $e->getMessage());
            $this->assertStringContainsString('Do not use APP_KEY', $e->getMessage());
        }
    }

    public function test_invalid_encryption_key_fails_clearly(): void
    {
        config(['jmb.encryption.key' => 'base64:'.base64_encode('too-short')]);

        $this->expectException(JmbEncryptionException::class);
        $this->expectExceptionMessage('JMB encryption key is invalid');

        JmbEncryptionService::fromConfig();
    }

    public function test_service_does_not_use_app_key_as_jmb_key(): void
    {
        $appRawKey = random_bytes(32);
        $appPreviousRawKey = random_bytes(32);
        config([
            'app.key' => 'base64:'.base64_encode($appRawKey),
            'app.previous_keys' => ['base64:'.base64_encode($appPreviousRawKey)],
        ]);

        $service = JmbEncryptionService::fromConfig();
        $ciphertext = $service->encrypt(self::SYNTHETIC_JMB);
        $this->assertIsString($ciphertext);

        $this->assertSame(self::SYNTHETIC_JMB, $service->decrypt($ciphertext));

        $payload = explode(':', $ciphertext, 3)[2];
        $appEncrypter = new Encrypter($appRawKey, (string) config('app.cipher'));

        try {
            $appEncrypter->decryptString($payload);
            $this->fail('APP_KEY Encrypter must not decrypt JMB payload.');
        } catch (DecryptException) {
            $this->assertTrue(true);
        }

        try {
            Crypt::decryptString($ciphertext);
            $this->fail('Crypt facade (APP_KEY) must not decrypt the JMB envelope.');
        } catch (DecryptException) {
            $this->assertTrue(true);
        }

        $wrongEnvelope = 'jmb:v1:'.$appEncrypter->encryptString(self::SYNTHETIC_JMB);

        try {
            $result = $service->decrypt($wrongEnvelope);
            $this->fail('JMB service must not decrypt APP_KEY ciphertext. Got: '.var_export($result, true));
        } catch (JmbEncryptionException $e) {
            $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $e->getMessage());
        }

        $previousAppEncrypter = new Encrypter($appPreviousRawKey, (string) config('app.cipher'));
        $previousEnvelope = 'jmb:v1:'.$previousAppEncrypter->encryptString(self::SYNTHETIC_JMB);

        try {
            $result = $service->decrypt($previousEnvelope);
        } catch (JmbEncryptionException $e) {
            $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $e->getMessage());

            return;
        }

        $this->fail('JMB service must not decrypt APP_KEY / APP_PREVIOUS_KEYS ciphertext. Got: '.var_export($result ?? null, true));
    }

    public function test_plaintext_is_not_logged_or_placed_in_exception_messages(): void
    {
        $source = (string) file_get_contents(base_path('app/Security/JmbEncryptionService.php'));
        $this->assertDoesNotMatchRegularExpression(
            '/\bLog::|\\\\Log::|->info\(|->error\(|->warning\(|->debug\(|->notice\(/',
            $source
        );

        $service = JmbEncryptionService::fromConfig();
        $ciphertext = $service->encrypt(self::SYNTHETIC_JMB);
        $this->assertIsString($ciphertext);

        try {
            $service->decrypt('jmb:v1:tampered');
            $this->fail('Tampered ciphertext must fail.');
        } catch (JmbEncryptionException $e) {
            $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $e->getMessage());
            $this->assertStringNotContainsString($ciphertext, $e->getMessage());
            $previous = $e->getPrevious();
            if ($previous !== null) {
                $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $previous->getMessage());
                $this->assertStringNotContainsString($ciphertext, $previous->getMessage());
            }
        }
    }

    public function test_refuses_to_double_encrypt_existing_envelope(): void
    {
        $service = JmbEncryptionService::fromConfig();
        $ciphertext = $service->encrypt(self::SYNTHETIC_JMB);

        $this->expectException(JmbEncryptionException::class);
        $this->expectExceptionMessage('already JMB ciphertext');

        $service->encrypt($ciphertext);
    }

    public function test_v1_ciphertext_remains_decryptable_after_active_key_rotates_to_v2(): void
    {
        $v1 = 'base64:'.base64_encode(random_bytes(32));
        $v2 = 'base64:'.base64_encode(random_bytes(32));

        config([
            'jmb.encryption.key' => $v1,
            'jmb.encryption.key_id' => 'v1',
            'jmb.encryption.previous_keys' => '',
        ]);
        $v1Ciphertext = JmbEncryptionService::fromConfig()->encrypt(self::SYNTHETIC_JMB);
        $this->assertIsString($v1Ciphertext);
        $this->assertStringStartsWith('jmb:v1:', $v1Ciphertext);

        config([
            'jmb.encryption.key' => $v2,
            'jmb.encryption.key_id' => 'v2',
            'jmb.encryption.previous_keys' => json_encode(['v1' => $v1], JSON_THROW_ON_ERROR),
        ]);
        $this->app->forgetInstance(JmbEncryptionService::class);
        $rotated = JmbEncryptionService::fromConfig();

        $this->assertSame(self::SYNTHETIC_JMB, $rotated->decrypt($v1Ciphertext));

        $v2Ciphertext = $rotated->encrypt(self::SYNTHETIC_JMB);
        $this->assertIsString($v2Ciphertext);
        $this->assertStringStartsWith('jmb:v2:', $v2Ciphertext);
        $this->assertSame(self::SYNTHETIC_JMB, $rotated->decrypt($v2Ciphertext));
        $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $v1Ciphertext);
        $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $v2Ciphertext);
    }

    public function test_unknown_key_id_fails_safely(): void
    {
        $service = JmbEncryptionService::fromConfig();
        $valid = $service->encrypt(self::SYNTHETIC_JMB);
        $this->assertIsString($valid);
        $payload = explode(':', $valid, 3)[2];

        try {
            $service->decrypt('jmb:v9:'.$payload);
            $this->fail('Unknown key_id must fail.');
        } catch (JmbEncryptionException $e) {
            $this->assertStringContainsString('Unsupported JMB encryption key id', $e->getMessage());
            $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $e->getMessage());
            $this->assertStringNotContainsString($payload, $e->getMessage());
        }
    }

    public function test_wrong_key_for_known_key_id_fails_safely_without_fallback(): void
    {
        $v1 = 'base64:'.base64_encode(random_bytes(32));
        $v1Wrong = 'base64:'.base64_encode(random_bytes(32));
        $v2 = 'base64:'.base64_encode(random_bytes(32));

        config([
            'jmb.encryption.key' => $v1,
            'jmb.encryption.key_id' => 'v1',
            'jmb.encryption.previous_keys' => '',
        ]);
        $v1Ciphertext = JmbEncryptionService::fromConfig()->encrypt(self::SYNTHETIC_JMB);
        $this->assertIsString($v1Ciphertext);

        config([
            'jmb.encryption.key' => $v2,
            'jmb.encryption.key_id' => 'v2',
            'jmb.encryption.previous_keys' => json_encode(['v1' => $v1Wrong], JSON_THROW_ON_ERROR),
        ]);
        $rotated = JmbEncryptionService::fromConfig();
        $v2Ciphertext = $rotated->encrypt(self::SYNTHETIC_JMB);
        $this->assertIsString($v2Ciphertext);
        $v2Payload = explode(':', $v2Ciphertext, 3)[2];
        $mislabelledV2AsV1 = 'jmb:v1:'.$v2Payload;

        foreach ([$v1Ciphertext, $mislabelledV2AsV1] as $ciphertext) {
            try {
                $result = $rotated->decrypt($ciphertext);
            } catch (JmbEncryptionException $e) {
                $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $e->getMessage());
                $this->assertStringNotContainsString($ciphertext, $e->getMessage());
                continue;
            }

            $this->fail('Decrypt must not fall back to another JMB key. Got: '.var_export($result, true));
        }
    }
}
