<?php

namespace App\Services\CulturalActivity;

use App\Exceptions\CulturalActivityRecordException;
use Carbon\CarbonInterface;

/**
 * Tipizovan ugovor za kasnije TS-012 emitere (F8-03).
 * Nije arbitrary request array.
 */
final class CulturalActivityRecordInput
{
    public const MAX_EVENT_ID_LENGTH = 191;

    public const MAX_EVENT_TYPE_LENGTH = 64;

    public const MAX_TARGET_TYPE_LENGTH = 64;

    public const MAX_CONTEXT_KEYS = 32;

    public const MAX_CONTEXT_VALUE_LENGTH = 191;

    /**
     * @param  array<string, scalar|null>  $context
     */
    public function __construct(
        public readonly string $sourceModule,
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly CarbonInterface $occurredAt,
        public readonly CulturalActivityActor $actor,
        public readonly string $targetType,
        public readonly ?int $targetId,
        public readonly ?int $organizerContextId = null,
        public readonly array $context = [],
    ) {
        CulturalActivitySourceModule::assertValid($sourceModule);
        $this->assertIdentity($eventId, 'event_id', self::MAX_EVENT_ID_LENGTH);
        $this->assertIdentity($eventType, 'event_type', self::MAX_EVENT_TYPE_LENGTH);
        $this->assertIdentity($targetType, 'target_type', self::MAX_TARGET_TYPE_LENGTH);

        if ($targetId !== null && $targetId < 1) {
            throw new CulturalActivityRecordException('target_id mora biti pozitivan identifikator ili null.');
        }

        if ($organizerContextId !== null && $organizerContextId < 1) {
            throw new CulturalActivityRecordException('organizer_context_id mora biti pozitivan identifikator ili null.');
        }

        $this->assertContext($context);
    }

    /**
     * @return array<string, mixed>
     */
    public function fingerprintPayload(): array
    {
        return [
            'event_type' => $this->eventType,
            'actor_type' => $this->actor->type,
            'actor_user_id' => $this->actor->userId,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'organizer_context_id' => $this->organizerContextId,
            'context' => $this->normalizedContext(),
        ];
    }

    /**
     * @return array<string, scalar|null>
     */
    public function normalizedContext(): array
    {
        $context = $this->context;
        if ($context === []) {
            return [];
        }

        ksort($context);

        return $context;
    }

    private function assertIdentity(string $value, string $field, int $maxLength): void
    {
        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed !== $value) {
            throw new CulturalActivityRecordException($field.' mora biti ne-prazan string bez vodećih/pratećih razmaka.');
        }

        if (strlen($trimmed) > $maxLength) {
            throw new CulturalActivityRecordException($field.' premašuje dozvoljenu dužinu.');
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function assertContext(array $context): void
    {
        if (count($context) > self::MAX_CONTEXT_KEYS) {
            throw new CulturalActivityRecordException('context ima previše ključeva.');
        }

        $denied = [
            'password', 'passwd', 'token', 'access_token', 'unsubscribe_token',
            'session', 'session_id', 'csrf', 'csrf_token', 'secret',
            'request', 'body', 'payload', 'payload_snapshot', 'ledger',
        ];

        foreach ($context as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                throw new CulturalActivityRecordException('context ključ mora biti ne-prazan string.');
            }

            if (in_array(strtolower($key), $denied, true)) {
                throw new CulturalActivityRecordException('context sadrži zabranjeni ključ.');
            }

            if (is_array($value) || is_object($value)) {
                throw new CulturalActivityRecordException('context ne smije sadržati ugniježđene strukture.');
            }

            if ($value !== null && ! is_scalar($value)) {
                throw new CulturalActivityRecordException('context vrijednost mora biti skalar ili null.');
            }

            if (is_string($value) && strlen($value) > self::MAX_CONTEXT_VALUE_LENGTH) {
                throw new CulturalActivityRecordException('context vrijednost premašuje dozvoljenu dužinu.');
            }
        }
    }
}
