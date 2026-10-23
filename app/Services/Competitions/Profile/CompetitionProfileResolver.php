<?php

namespace App\Services\Competitions\Profile;

use App\Support\CompetitionProgramCatalog;

/**
 * Profile policy SSOT for identity and legal/runtime behavior.
 * Public visibility remains CompetitionProgramCatalog (active/development).
 */
final class CompetitionProfileResolver
{
    public const TYPE_WOMEN = 'zensko';

    public const TYPE_YOUTH = 'omladinsko';

    public const TYPE_CANONICAL = 'preduzetnici_mikro_mala_i_srednja_preduzeca';

    public const TYPE_OTHER = 'ostalo';

    public static function resolve(?string $type): CompetitionProfilePolicy
    {
        return match ($type) {
            self::TYPE_WOMEN => new WomenPolicy(),
            self::TYPE_YOUTH => new YouthPolicy(),
            self::TYPE_CANONICAL => new CanonicalMmspPolicy(),
            default => new FailClosedProfilePolicy($type),
        };
    }

    /**
     * Values the competitions.type column may store.
     * ostalo remains storable and fail-closed. It is not canonical.
     *
     * @return list<string>
     */
    public static function storableKeys(): array
    {
        return [
            self::TYPE_WOMEN,
            self::TYPE_YOUTH,
            self::TYPE_OTHER,
            self::TYPE_CANONICAL,
        ];
    }

    public static function validationRule(): string
    {
        return 'required|in:'.implode(',', self::storableKeys());
    }

    /**
     * Public listing/show keys — delegates to Catalog (visibility SSOT).
     * Do not derive from policy publicationMode.
     *
     * @return list<string>
     */
    public static function publiclyDiscoverableTypeKeys(): array
    {
        return CompetitionProgramCatalog::publiclyAvailableTypes();
    }

    /**
     * Admin create/edit options. Existing labels are unchanged.
     *
     * @return array<string, string>
     */
    public static function adminTypeOptions(): array
    {
        return [
            self::TYPE_WOMEN => self::resolve(self::TYPE_WOMEN)->displayName(),
            self::TYPE_YOUTH => self::resolve(self::TYPE_YOUTH)->displayName(),
            self::TYPE_CANONICAL => self::resolve(self::TYPE_CANONICAL)->displayName(),
            self::TYPE_OTHER => self::resolve(self::TYPE_OTHER)->displayName(),
        ];
    }
}
