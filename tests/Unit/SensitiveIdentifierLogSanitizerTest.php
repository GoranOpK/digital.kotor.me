<?php

namespace Tests\Unit;

use App\Support\SensitiveIdentifierLogSanitizer;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SensitiveIdentifierLogSanitizerTest extends TestCase
{
    private const SYNTHETIC_JMB = '0101990009015';

    public function test_top_level_applicant_jmbg_is_redacted_without_hashing(): void
    {
        $redacted = SensitiveIdentifierLogSanitizer::redact([
            'application_id' => 42,
            'applicant_jmbg' => self::SYNTHETIC_JMB,
            'applicant_name' => 'Ana Test',
        ]);

        $this->assertSame(42, $redacted['application_id']);
        $this->assertSame('Ana Test', $redacted['applicant_name']);
        $this->assertSame('[REDACTED]', $redacted['applicant_jmbg']);
        $this->assertIdentifierAbsentFromDump($redacted);
    }

    public function test_nested_jmb_keys_are_redacted(): void
    {
        $redacted = SensitiveIdentifierLogSanitizer::redact([
            'db_scalars' => [
                'applicant_name' => 'Ana Test',
                'applicant_jmbg' => self::SYNTHETIC_JMB,
            ],
            'payload' => [
                'nested' => [
                    'physical_person_jmbg' => self::SYNTHETIC_JMB,
                    'authorized_jmb' => self::SYNTHETIC_JMB,
                    'representative_jmb' => self::SYNTHETIC_JMB,
                ],
            ],
        ]);

        $this->assertSame('Ana Test', $redacted['db_scalars']['applicant_name']);
        $this->assertSame('[REDACTED]', $redacted['db_scalars']['applicant_jmbg']);
        $this->assertSame('[REDACTED]', $redacted['payload']['nested']['physical_person_jmbg']);
        $this->assertSame('[REDACTED]', $redacted['payload']['nested']['authorized_jmb']);
        $this->assertSame('[REDACTED]', $redacted['payload']['nested']['representative_jmb']);
        $this->assertIdentifierAbsentFromDump($redacted);
    }

    public function test_key_case_and_separator_variants_do_not_bypass_sanitizer(): void
    {
        $redacted = SensitiveIdentifierLogSanitizer::redact([
            'JMB' => self::SYNTHETIC_JMB,
            'Jmbg' => self::SYNTHETIC_JMB,
            'Applicant_JMBG' => self::SYNTHETIC_JMB,
            'physicalPersonJmbg' => self::SYNTHETIC_JMB,
            'authorized-jmb' => self::SYNTHETIC_JMB,
            'representative_jmbg' => self::SYNTHETIC_JMB,
            'jmb' => self::SYNTHETIC_JMB,
        ]);

        foreach (array_keys($redacted) as $key) {
            $this->assertSame('[REDACTED]', $redacted[$key], $key);
        }

        $this->assertIdentifierAbsentFromDump($redacted);
    }

    public function test_non_sensitive_diagnostic_fields_remain(): void
    {
        $redacted = SensitiveIdentifierLogSanitizer::redact([
            'application_id' => 7,
            'business_idea_name' => 'Ideja',
            'applicant_name' => 'Ana Test',
            'applicant_email' => 'ana@example.test',
            'applicant_phone' => '067000000',
            'summary_len' => 12,
            'is_draft' => true,
            'request_keys' => ['business_idea_name', 'applicant_jmbg'],
        ]);

        $this->assertSame(7, $redacted['application_id']);
        $this->assertSame('Ideja', $redacted['business_idea_name']);
        $this->assertSame('Ana Test', $redacted['applicant_name']);
        $this->assertSame('ana@example.test', $redacted['applicant_email']);
        $this->assertSame('067000000', $redacted['applicant_phone']);
        $this->assertSame(12, $redacted['summary_len']);
        $this->assertTrue($redacted['is_draft']);
        $this->assertSame(['business_idea_name', 'applicant_jmbg'], $redacted['request_keys']);
    }

    public function test_arrayable_and_json_payloads_are_sanitized(): void
    {
        $redacted = SensitiveIdentifierLogSanitizer::redact([
            'from_collection' => Collection::make([
                'applicant_jmbg' => self::SYNTHETIC_JMB,
                'applicant_name' => 'Ana Test',
            ]),
            'from_object' => (object) [
                'jmb' => self::SYNTHETIC_JMB,
                'city' => 'Kotor',
            ],
        ]);

        $this->assertSame('[REDACTED]', $redacted['from_collection']['applicant_jmbg']);
        $this->assertSame('Ana Test', $redacted['from_collection']['applicant_name']);
        $this->assertSame('[REDACTED]', $redacted['from_object']['jmb']);
        $this->assertSame('Kotor', $redacted['from_object']['city']);
        $this->assertIdentifierAbsentFromDump($redacted);
    }

    /**
     * @param  mixed  $value
     */
    private function assertIdentifierAbsentFromDump(mixed $value): void
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString(self::SYNTHETIC_JMB, $encoded);
        $this->assertStringNotContainsString(hash('sha256', self::SYNTHETIC_JMB), $encoded);
        $this->assertStringNotContainsString(base64_encode(self::SYNTHETIC_JMB), $encoded);
        $this->assertStringNotContainsString(substr(self::SYNTHETIC_JMB, -4), $encoded);
    }
}
