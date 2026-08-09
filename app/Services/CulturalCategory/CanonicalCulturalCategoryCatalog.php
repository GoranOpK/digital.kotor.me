<?php

namespace App\Services\CulturalCategory;

/**
 * SSOT početnog V1 kataloga kategorija (TS7-PO-07 / BM-KO-09 / BR-277).
 * Nije ENUM — katalog ostaje proširiv; ova lista je samo CAT-14 preduslov.
 */
final class CanonicalCulturalCategoryCatalog
{
    /**
     * Usvojeni redoslijed (1–14).
     *
     * @var list<string>
     */
    public const NAMES = [
        'Koncerti',
        'Predstave',
        'Sportski događaji',
        'Izložbe',
        'Književni programi',
        'Filmske projekcije',
        'Dječiji programi',
        'Konferencije',
        'Radionice',
        'Publikacije',
        'Performansi',
        'Prezentacije i predavanja',
        'Paneli i tribine',
        'Sajmovi',
    ];

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return self::NAMES;
    }

    public static function count(): int
    {
        return count(self::NAMES);
    }
}
