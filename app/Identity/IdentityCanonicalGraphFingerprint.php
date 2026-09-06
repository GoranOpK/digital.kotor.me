<?php

namespace App\Identity;

use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;

/**
 * Deterministic Step 4 identity graph fingerprint.
 * Shared by backfill idempotency and Step 5 verify. No extra normalization.
 */
final class IdentityCanonicalGraphFingerprint
{
    /**
     * @return array<string, mixed>
     */
    public static function fromSnapshot(IdentitySnapshot $snapshot): array
    {
        $fl = $snapshot->physicalPerson;

        return [
            'user_id' => $snapshot->userId,
            'subject_type' => $snapshot->subjectType,
            'mobile_phone' => self::outerTrim($snapshot->mobilePhone),
            'first_name' => $fl?->firstName,
            'last_name' => $fl?->lastName,
            'residential_status' => $fl?->residentialStatus,
            'id_document_type' => $fl?->idDocumentType,
            'jmb' => $fl?->jmb,
            'passport_number' => $fl?->passportNumber,
            'residence_country_code' => $fl?->residenceCountryCode,
            'is_entrepreneur' => $fl?->isEntrepreneur === true,
            'entrepreneur_business_name' => $fl?->entrepreneurBusinessName,
            'pib' => $fl?->pib,
            'crps_number' => $fl?->crpsNumber,
            'street_and_number' => $fl?->streetAndNumber,
            'city' => $fl?->city,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromPersistedPhysicalPersonGraph(PlatformIdentity $platform, PhysicalPersonIdentity $fl): array
    {
        return [
            'user_id' => (int) $platform->user_id,
            'subject_type' => $platform->subject_type,
            'mobile_phone' => self::outerTrim($platform->mobile_phone),
            'first_name' => $fl->first_name,
            'last_name' => $fl->last_name,
            'residential_status' => $fl->residential_status,
            'id_document_type' => $fl->id_document_type,
            'jmb' => $fl->jmb,
            'passport_number' => self::outerTrim($fl->passport_number),
            'residence_country_code' => self::outerTrim($fl->residence_country_code),
            'is_entrepreneur' => $fl->is_entrepreneur === true,
            'entrepreneur_business_name' => self::outerTrim($fl->entrepreneur_business_name),
            'pib' => self::outerTrim($fl->pib),
            'crps_number' => self::outerTrim($fl->crps_number),
            'street_and_number' => $fl->street_and_number,
            'city' => $fl->city,
        ];
    }

    public static function outerTrim(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
