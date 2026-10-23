<?php

namespace App\Support;

/**
 * Canonical MMSP subsidy types (Odluka čl.12 tačke 1–3) and čl.19 cap metadata.
 *
 * Persisted identity is the stable code, never the article number. The čl.19
 * reference to "član 13 tačka 1" resolves to čl.12 tačka 1 (PO Q1 RESOLVED).
 * Cap percentages are legal metadata only; the cap itself is Commission outcome logic.
 */
final class CanonicalSubsidyType
{
    public const AUTHENTIC_LOCAL = 'autenticni_lokalni_proizvodi_usluge';

    public const INNOVATIVE = 'inovativni_proizvodi_usluge';

    public const DIGITALIZATION = 'digitalizacija';

    public const BUDGET_CAP_PERCENTAGE = 20;

    public const MAX_APPLICATIONS_PER_BENEFICIARY = 2;

    /**
     * @var array<string, array{label: string, article: int, point: int, cap_percentage: int}>
     */
    private const DEFINITIONS = [
        self::AUTHENTIC_LOCAL => [
            'label' => 'Subvencije za razvoj autentičnih lokalnih proizvoda/usluga',
            'article' => 12,
            'point' => 1,
            'cap_percentage' => 80,
        ],
        self::INNOVATIVE => [
            'label' => 'Subvencije za razvoj inovativnih proizvoda/usluga',
            'article' => 12,
            'point' => 2,
            'cap_percentage' => 50,
        ],
        self::DIGITALIZATION => [
            'label' => 'Subvencije za digitalizaciju',
            'article' => 12,
            'point' => 3,
            'cap_percentage' => 50,
        ],
    ];

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    public static function isValid(mixed $code): bool
    {
        return is_string($code) && array_key_exists($code, self::DEFINITIONS);
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_map(fn (array $definition) => $definition['label'], self::DEFINITIONS);
    }

    public static function label(string $code): string
    {
        return self::definition($code)['label'];
    }

    /**
     * Order number within čl.12 (1–3).
     */
    public static function order(string $code): int
    {
        return self::definition($code)['point'];
    }

    public static function articleReference(string $code): string
    {
        $definition = self::definition($code);

        return 'čl.'.$definition['article'].' tačka '.$definition['point'];
    }

    /**
     * čl.19 share of required funds (P2 ukupna vrijednost ulaganja) for this type.
     */
    public static function capPercentage(string $code): int
    {
        return self::definition($code)['cap_percentage'];
    }

    /**
     * @return array{label: string, article: int, point: int, cap_percentage: int}
     */
    private static function definition(string $code): array
    {
        if (! self::isValid($code)) {
            throw new \InvalidArgumentException('Nepoznata vrsta kanonske subvencije: '.$code);
        }

        return self::DEFINITIONS[$code];
    }
}
