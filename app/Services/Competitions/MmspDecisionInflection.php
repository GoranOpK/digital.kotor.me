<?php

namespace App\Services\Competitions;

/**
 * Gramatički oblici za tekst MMSP odluke.
 * Ne računa prijave, prigovore ni iznose.
 */
final class MmspDecisionInflection
{
    public static function applications(int $count): string
    {
        return $count.' '.self::form($count, 'prijava', 'prijave', 'prijava');
    }

    public static function plans(int $count): string
    {
        return self::form($count, 'plan', 'plana', 'planova');
    }

    public static function days(int $count): string
    {
        return $count.' '.self::form($count, 'dan', 'dana', 'dana');
    }

    public static function firstSessionParagraph(
        string $sessionDate,
        int $received,
        int $originalIncomplete,
        int $accepted,
        int $rejected,
        int $proceed,
    ): string {
        return 'Nakon isteka roka za podnošenje prijava na Javni poziv, Komisija je na prvoj sjednici održanoj dana '
            .$sessionDate.'. godine utvrdila '.self::receivedClause($received)
            .'. Komisija je pregledala elektronski zaprimljene prijave, nakon čega je '
            .self::incompleteClause($originalIncomplete)
            .'. Podnosioci/podnositeljke nepotpunih prijava imali/e su pravo Prigovora Komisiji. '
            .self::appealSentence($accepted, $rejected)
            .' Komisija je konstatovala da '
            .self::proceedClause($proceed)
            .' u dalje razmatranje i vrednovanje po kriterijumima utvrđenim Odlukom i Javnim pozivom.';
    }

    public static function form(int $count, string $one, string $few, string $many): string
    {
        return match (self::category($count)) {
            'one' => $one,
            'few' => $few,
            default => $many,
        };
    }

    private static function category(int $count): string
    {
        $mod10 = abs($count) % 10;
        $mod100 = abs($count) % 100;
        if ($mod10 === 1 && $mod100 !== 11) {
            return 'one';
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return 'few';
        }

        return 'many';
    }

    private static function receivedClause(int $count): string
    {
        if ($count === 0) {
            return 'da nije pristigla nijedna prijava';
        }

        return match (self::category($count)) {
            'one' => 'da je pristigla ukupno '.$count.' prijava',
            'few' => 'da su pristigle ukupno '.$count.' prijave',
            default => 'da je pristiglo ukupno '.$count.' prijava',
        };
    }

    private static function incompleteClause(int $count): string
    {
        if ($count === 0) {
            return 'konstatovala da prvobitno nepotpunih prijava nema';
        }

        return match (self::category($count)) {
            'one' => 'konstatovala da je prvobitno '.$count.' prijava nepotpuna u smislu priložene dokumentacije, te ista nije dalje razmatrana',
            'few' => 'konstatovala da su prvobitno '.$count.' prijave nepotpune u smislu priložene dokumentacije, te iste nijesu dalje razmatrane',
            default => 'konstatovala da je prvobitno '.$count.' prijava nepotpuno u smislu priložene dokumentacije, te iste nijesu dalje razmatrane',
        };
    }

    private static function proceedClause(int $count): string
    {
        if ($count === 0) {
            return 'nijedna prijava ne može biti uzeta';
        }

        return match (self::category($count)) {
            'one' => $count.' prijava može biti uzeta',
            'few' => $count.' prijave mogu biti uzete',
            default => $count.' prijava može biti uzeto',
        };
    }

    private static function appealSentence(int $accepted, int $rejected): string
    {
        $parts = [];
        if ($accepted > 0) {
            $parts[] = 'prihvatanju '.self::appeals($accepted);
        }
        if ($rejected > 0) {
            $parts[] = 'odbijanju '.self::appeals($rejected);
        }
        if ($parts === []) {
            return 'Prigovora nije bilo, te Komisija nije donijela odluku o prihvatanju ili odbijanju prigovora.';
        }

        return 'Komisija je donijela odluku o '.implode(' i ', $parts).'.';
    }

    private static function appeals(int $count): string
    {
        return $count.' '.self::form($count, 'prigovora', 'prigovora', 'prigovora');
    }
}
