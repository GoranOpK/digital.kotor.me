<?php

namespace App\Support;

/**
 * Kanonske definicije konkursnih profila.
 * Javni katalog i početak prijave koriste samo profile sa statusom `active`.
 */
final class CompetitionProgramCatalog
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_DEVELOPMENT = 'development';

    public const PROFILE_UNAVAILABLE_FOR_APPLICATIONS_MESSAGE = 'Ovaj profil konkursa još nije dostupan za podnošenje prijava.';

    /**
     * @var array<string, string>|null
     */
    private static ?array $testStatusOverrides = null;

    /**
     * @return array<string, array{type: string, title: string, description: string, status: string, icon: string}>
     */
    public static function definitions(): array
    {
        $definitions = self::canonicalDefinitions();

        if (self::$testStatusOverrides === null) {
            return $definitions;
        }

        foreach (self::$testStatusOverrides as $type => $status) {
            if (! isset($definitions[$type])) {
                continue;
            }

            $definitions[$type]['status'] = $status;
        }

        return $definitions;
    }

    /**
     * @return list<string>
     */
    public static function publiclyAvailableTypes(): array
    {
        $types = [];

        foreach (self::definitions() as $type => $definition) {
            if (($definition['status'] ?? '') === self::STATUS_ACTIVE) {
                $types[] = $type;
            }
        }

        return $types;
    }

    public static function isPubliclyAvailable(?string $type): bool
    {
        return is_string($type) && in_array($type, self::publiclyAvailableTypes(), true);
    }

    public static function overrideStatusForTests(string $type, string $status): void
    {
        self::$testStatusOverrides[$type] = $status;
    }

    public static function clearTestOverrides(): void
    {
        self::$testStatusOverrides = null;
    }

    /**
     * @return array<string, array{type: string, title: string, description: string, status: string, icon: string}>
     */
    private static function canonicalDefinitions(): array
    {
        return [
            'zensko' => [
                'type' => 'zensko',
                'title' => 'Podrška ženskom preduzetništvu',
                'description' => 'Kompletan modul — prijave, dokumentacija, komisija i evaluacija.',
                'status' => self::STATUS_ACTIVE,
                'icon' => '👩‍💼',
            ],
            'omladinsko' => [
                'type' => 'omladinsko',
                'title' => 'Podrška preduzetništvu mladih',
                'description' => 'Profil je u implementaciji i još nije pušten korisnicima.',
                'status' => self::STATUS_DEVELOPMENT,
                'icon' => '🎓',
            ],
        ];
    }
}
