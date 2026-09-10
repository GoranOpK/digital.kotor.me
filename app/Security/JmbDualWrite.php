<?php

namespace App\Security;

use Illuminate\Database\Eloquent\Model;

/**
 * Persist logical JMB/JMBG to parallel encrypted (and lookup) columns.
 *
 * Logical incoming value is explicit via assignLogical(), or — when
 * retirement is off — a dirty plaintext attribute (production contract).
 * Plaintext NULL is not a logical clear unless assignLogical(null) was used,
 * or retirement is off and the plaintext column was dirtied to empty.
 */
final class JmbDualWrite
{
    /**
     * @var array<class-string<Model>, array<string, string>>
     */
    public const MODEL_COLUMNS = [
        \App\Models\User::class => ['jmb' => 'jmb_encrypted'],
        \App\Models\PhysicalPersonIdentity::class => ['jmb' => 'jmb_encrypted'],
        \App\Models\LegalEntityAuthorizedPerson::class => ['jmb' => 'jmb_encrypted'],
        \App\Models\ForeignBranchRepresentative::class => ['jmb' => 'jmb_encrypted'],
        \App\Models\Application::class => [
            'physical_person_jmbg' => 'physical_person_jmbg_encrypted',
            'applicant_jmbg' => 'applicant_jmbg_encrypted',
        ],
        \App\Models\BusinessPlan::class => ['applicant_jmbg' => 'applicant_jmbg_encrypted'],
    ];

    /**
     * @var list<class-string<Model>>
     */
    public const LOOKUP_MODELS = [
        \App\Models\User::class,
        \App\Models\PhysicalPersonIdentity::class,
    ];

    public static function isRetirementEnabled(): bool
    {
        return (bool) config('jmb.plaintext_retirement.enabled', false);
    }

    public static function assignLogical(
        Model $model,
        string $plaintextColumn,
        #[\SensitiveParameter] ?string $value,
    ): void {
        $pairs = self::MODEL_COLUMNS[$model::class] ?? null;
        if ($pairs === null || ! array_key_exists($plaintextColumn, $pairs)) {
            throw new \InvalidArgumentException('Unsupported JMB column for logical assignment.');
        }

        $logical = self::normalizeIncoming($value);
        $model->recordLogicalJmb($plaintextColumn, $logical);
        $model->setAttribute(
            $plaintextColumn,
            self::isRetirementEnabled() ? null : $logical
        );
    }

    public static function syncModel(Model $model): void
    {
        $pairs = self::MODEL_COLUMNS[$model::class] ?? null;
        if ($pairs === null) {
            return;
        }

        $logicalUpdates = $model->peekLogicalJmbUpdates();
        $retirement = self::isRetirementEnabled();

        foreach ($pairs as $plaintextColumn => $encryptedColumn) {
            if (array_key_exists($plaintextColumn, $logicalUpdates)) {
                self::persistLogical(
                    $model,
                    $plaintextColumn,
                    $encryptedColumn,
                    $logicalUpdates[$plaintextColumn]
                );

                continue;
            }

            if (! $model->isDirty($plaintextColumn)) {
                continue;
            }

            $incoming = self::normalizeIncoming(
                self::attributeAsString($model->getAttribute($plaintextColumn))
            );

            if ($retirement && $incoming === null) {
                $model->setAttribute($plaintextColumn, null);

                continue;
            }

            self::persistLogical($model, $plaintextColumn, $encryptedColumn, $incoming);
        }

        $model->takeLogicalJmbUpdates();
    }

    private static function persistLogical(
        Model $model,
        string $plaintextColumn,
        string $encryptedColumn,
        #[\SensitiveParameter] ?string $logical,
    ): void {
        $model->setAttribute(
            $plaintextColumn,
            self::isRetirementEnabled() ? null : $logical
        );

        if ($logical === null) {
            $model->setAttribute($encryptedColumn, null);
            self::syncLookup($model, $plaintextColumn, null);

            return;
        }

        if (self::storedMatches($model, $encryptedColumn, $logical)) {
            self::syncLookupIfNeeded($model, $plaintextColumn, $logical);

            return;
        }

        self::syncLookup($model, $plaintextColumn, $logical);
        $model->setAttribute($encryptedColumn, self::encryption()->encrypt($logical));
    }

    private static function storedMatches(
        Model $model,
        string $encryptedColumn,
        #[\SensitiveParameter] string $logical,
    ): bool {
        $encrypted = $model->getAttribute($encryptedColumn);
        if (! is_string($encrypted) || $encrypted === '') {
            return false;
        }

        try {
            return self::encryption()->decrypt($encrypted) === $logical;
        } catch (JmbEncryptionException) {
            return false;
        }
    }

    private static function syncLookupIfNeeded(
        Model $model,
        string $plaintextColumn,
        #[\SensitiveParameter] string $logical,
    ): void {
        if ($plaintextColumn !== 'jmb' || ! in_array($model::class, self::LOOKUP_MODELS, true)) {
            return;
        }

        $expected = self::lookup()->digest($logical);
        if ($model->getAttribute('jmb_lookup') === $expected) {
            return;
        }

        $model->setAttribute('jmb_lookup', $expected);
    }

    private static function syncLookup(
        Model $model,
        string $plaintextColumn,
        #[\SensitiveParameter] ?string $logical,
    ): void {
        if ($plaintextColumn !== 'jmb' || ! in_array($model::class, self::LOOKUP_MODELS, true)) {
            return;
        }

        if ($logical === null) {
            $model->setAttribute('jmb_lookup', null);

            return;
        }

        $model->setAttribute('jmb_lookup', self::lookup()->digest($logical));
    }

    private static function normalizeIncoming(#[\SensitiveParameter] ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function attributeAsString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        return null;
    }

    private static function encryption(): JmbEncryptionService
    {
        return app(JmbEncryptionService::class);
    }

    private static function lookup(): JmbLookupService
    {
        return app(JmbLookupService::class);
    }
}
