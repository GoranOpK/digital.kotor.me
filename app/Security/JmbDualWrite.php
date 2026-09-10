<?php

namespace App\Security;

use Illuminate\Database\Eloquent\Model;

/**
 * Phase C1: keep parallel *_encrypted columns in sync on Eloquent persist.
 * Does not change reads or uniqueness. Does not re-encrypt unchanged JMB values.
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

                continue;
            }

            $model->setAttribute(
                $encryptedColumn,
                self::encryption()->encrypt((string) $plaintext)
            );
        }
    }

    private static function encryption(): JmbEncryptionService
    {
        return app(JmbEncryptionService::class);
    }
}
