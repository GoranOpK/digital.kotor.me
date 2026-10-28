<?php

namespace App\Services\Competitions;

use RuntimeException;

/**
 * Kontrolisana greška prikaza. Poruka namjerno nema putanju ni druge tehničke detalje.
 */
class ZenskoDecisionProposalReadException extends RuntimeException
{
    public const MESSAGE = 'Sačuvani Predlog odluke nije moguće prikazati jer provjera integriteta dokumenta nije uspjela.';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
