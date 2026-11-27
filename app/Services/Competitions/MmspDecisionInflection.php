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
            .'. Podnosioci/podnositeljke nepotpunih prijava imali/e su pravo Prigovora Komisiji, nakon čega je Komisija donijela odluku o prihvatanju '
            .$accepted.' prigovora ili odbijanju '.$rejected
            .' prigovora. Komisija je konstatovala da '
            .self::proceedClause($proceed)
            .' u dalje razmatranje i vrednovanje po kriterijumima utvrđenim Odlukom i Javnim pozivom.';
    }

    public static function form(int $count, string $one, string $few, string $many): string
    {
        $mod10 = abs($count) % 10;
        $mod100 = abs($count) % 100;
        if ($mod10 === 1 && $mod100 !== 11) {
            return $one;
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return $few;
        }

        return $many;
    }

    private static function receivedClause(int $count): string
    {
        if ($count === 0) {
            return 'da nije pristigla nijedna prijava';
        }
        if ($count === 1) {
            return 'da je pristigla ukupno 1 prijava';
        }
        $word = self::form($count, 'prijava', 'prijave', 'prijava');
        if ($word === 'prijave') {
            return 'da su pristigle ukupno '.$count.' prijave';
        }

        return 'da je pristiglo ukupno '.$count.' prijava';
    }

    private static function incompleteClause(int $count): string
    {
        if ($count === 0) {
            return 'konstatovala da prvobitno nepotpunih prijava nema';
        }
        if ($count === 1) {
            return 'konstatovala da je prvobitno 1 prijava nepotpuna u smislu priložene dokumentacije, te ista nije dalje razmatrana';
        }
        $word = self::form($count, 'prijava', 'prijave', 'prijava');
        if ($word === 'prijave') {
            return 'konstatovala da su prvobitno '.$count.' prijave nepotpune u smislu priložene dokumentacije, te iste nijesu dalje razmatrane';
        }

        return 'konstatovala da je prvobitno '.$count.' prijava nepotpuno u smislu priložene dokumentacije, te iste nijesu dalje razmatrane';
    }

    private static function proceedClause(int $count): string
    {
        if ($count === 0) {
            return 'nijedna prijava ne može biti uzeta';
        }
        if ($count === 1) {
            return '1 prijava može biti uzeta';
        }
        $word = self::form($count, 'prijava', 'prijave', 'prijava');
        if ($word === 'prijave') {
            return $count.' prijave mogu biti uzete';
        }

        return $count.' prijava mogu biti uzete';
    }
}
