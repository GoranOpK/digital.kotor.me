<?php

namespace App\Security;

use Illuminate\Database\Eloquent\Model;

/**
 * Keep parallel *_encrypted columns in sync on Eloquent persist.
 * For User and PhysicalPersonIdentity, also sync jmb_lookup from jmb.
 * Does not change reads or uniqueness. Does not recompute unchanged JMB values.
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

    public static function syncModel(Model $model): void
    {
        $pairs = self::MODEL_COLUMNS[$model::class] ?? null;
        if ($pairs === null) {
            return;
        }

        foreach ($pairs as $plaintextColumn => $encryptedColumn) {
            if (! $model->isDirty($plaintextColumn)) {
                continue;
            }

            $plaintext = $model->getAttribute($plaintextColumn);
            if ($plaintext === null || $plaintext === '') {
                $model->setAttribute($encryptedColumn, null);
                self::syncLookup($model, $plaintextColumn, null);

                continue;
            }

            $value = (string) $plaintext;
            self::syncLookup($model, $plaintextColumn, $value);
            $model->setAttribute(
                $encryptedColumn,
                self::encryption()->encrypt($value)
            );
        }
    }

    private static function syncLookup(Model $model, string $plaintextColumn, #[\SensitiveParameter] ?string $plaintext): void
    {
        if ($plaintextColumn !== 'jmb' || ! in_array($model::class, self::LOOKUP_MODELS, true)) {
            return;
        }

        if ($plaintext === null || $plaintext === '') {
            $model->setAttribute('jmb_lookup', null);

            return;
        }

        $model->setAttribute('jmb_lookup', self::lookup()->digest($plaintext));
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
