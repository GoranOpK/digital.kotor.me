<?php

namespace App\Support;

use App\Models\Application;
use App\Models\Competition;
use DomainException;

/**
 * Godišnja instanca i budžetska pravila prvog i drugog Poziva profila mladih.
 *
 * Radi nad postojećim Competition i Application modelima. Ne primjenjuje se na zensko.
 * Ne kreira Poziv, ne mijenja objavljeni budget prvog Poziva i ne uvodi remaining_amount.
 */
final class CompetitionAnnualInstance
{
    public const PROFILE_OMLADINSKO = 'omladinsko';

    public const PROFILE_ZENSKO = 'zensko';

    public const CALL_FIRST = 1;

    public const CALL_SECOND = 2;

    /**
     * Existing application values from the adopted competition flow. Not new status names.
     */
    public const CONFIRMED_APPLICATION_STATUS = 'approved';

    public const CONFIRMED_COMMISSION_DECISION = 'podrzava_potpuno';

    public function appliesTo(?string $type): bool
    {
        return $type === self::PROFILE_OMLADINSKO;
    }

    public function findFirstCall(string $type, int $year): ?Competition
    {
        if (! $this->appliesTo($type)) {
            return null;
        }

        return $this->findCall($type, $year, self::CALL_FIRST);
    }

    public function findSecondCall(string $type, int $year): ?Competition
    {
        if (! $this->appliesTo($type)) {
            return null;
        }

        return $this->findCall($type, $year, self::CALL_SECOND);
    }

    public function confirmedAllocation(Competition $call): string
    {
        $this->assertBcMath();

        $amounts = Application::query()
            ->where('competition_id', $call->id)
            ->where('status', self::CONFIRMED_APPLICATION_STATUS)
            ->where('commission_decision', self::CONFIRMED_COMMISSION_DECISION)
            ->whereNotNull('approved_amount')
            ->pluck('approved_amount');

        $total = '0.00';

        foreach ($amounts as $amount) {
            $total = bcadd($total, $this->normalizeDecimal((string) $amount), 2);
        }

        return $this->normalizeDecimal($total);
    }

    public function remainingAfterFirst(string $type, int $year): string
    {
        $this->assertOmladinskoType($type);
        $this->assertBcMath();

        $first = $this->requireFirstCall($type, $year);
        $annual = $this->requirePositiveDecimal((string) $first->annual_budget, 'annual_budget');
        $allocated = $this->confirmedAllocation($first);

        if (bccomp($allocated, $annual, 2) === 1) {
            throw new DomainException(
                'Konačno potvrđena raspodjela prvog Poziva premašuje godišnji okvir. Prekoračenje se ne prikriva.'
            );
        }

        return bcsub($annual, $allocated, 2);
    }

    public function validateFirstCall(Competition $competition): void
    {
        if (! $this->appliesTo($competition->type)) {
            return;
        }

        $this->assertCallNumber($competition->call_number, self::CALL_FIRST);
        $this->requirePositiveDecimal((string) $competition->annual_budget, 'annual_budget');
        $this->requirePositiveDecimal((string) $competition->budget, 'budget');
        $this->assertYear($competition);

        $existing = $this->findCall(
            (string) $competition->type,
            (int) $competition->year,
            self::CALL_FIRST
        );

        if ($existing && (int) $existing->id !== (int) $competition->id) {
            throw new DomainException(
                'Prvi Poziv iste godišnje instance već postoji.'
            );
        }
    }

    public function validateSecondCall(Competition $second): void
    {
        if (! $this->appliesTo($second->type)) {
            return;
        }

        $this->assertCallNumber($second->call_number, self::CALL_SECOND);
        $this->assertYear($second);

        $type = (string) $second->type;
        $year = (int) $second->year;
        $first = $this->requireFirstCall($type, $year);
        $publishedFirstBudget = $this->normalizeDecimal((string) $first->budget);

        $this->requirePositiveDecimal((string) $second->annual_budget, 'annual_budget');

        if (bccomp(
            $this->normalizeDecimal((string) $second->annual_budget),
            $this->normalizeDecimal((string) $first->annual_budget),
            2
        ) !== 0) {
            throw new DomainException(
                'annual_budget drugog Poziva mora biti isti kao na prvom Pozivu.'
            );
        }

        $budget = $this->requirePositiveDecimal((string) $second->budget, 'budget');
        $remaining = $this->remainingAfterFirst($type, $year);

        if (bccomp($budget, $remaining, 2) === 1) {
            throw new DomainException(
                'Budžet drugog Poziva ne smije premašiti preostala godišnja sredstva.'
            );
        }

        $existingSecond = $this->findCall($type, $year, self::CALL_SECOND);

        if ($existingSecond && (int) $existingSecond->id !== (int) $second->id) {
            throw new DomainException(
                'Drugi Poziv iste godišnje instance već postoji.'
            );
        }

        $first->refresh();

        if (bccomp($this->normalizeDecimal((string) $first->budget), $publishedFirstBudget, 2) !== 0) {
            throw new DomainException(
                'Objavljeni budget prvog Poziva se ne smije mijenjati.'
            );
        }
    }

    public function assertCallNumberAllowed(?int $callNumber, string $type): void
    {
        if (! $this->appliesTo($type)) {
            return;
        }

        if ($callNumber !== self::CALL_FIRST && $callNumber !== self::CALL_SECOND) {
            throw new DomainException('Treći Poziv nije dozvoljen. Dozvoljeni su samo call_number 1 ili 2.');
        }
    }

    private function findCall(string $type, int $year, int $callNumber): ?Competition
    {
        return Competition::query()
            ->where('type', $type)
            ->where('year', $year)
            ->where('call_number', $callNumber)
            ->first();
    }

    private function requireFirstCall(string $type, int $year): Competition
    {
        $first = $this->findFirstCall($type, $year);

        if (! $first) {
            throw new DomainException(
                'Drugi Poziv zahtijeva postojeći prvi Poziv iste godišnje instance.'
            );
        }

        return $first;
    }

    private function assertOmladinskoType(string $type): void
    {
        if (! $this->appliesTo($type)) {
            throw new DomainException(
                'Godišnja instanca i remaining_after_first primjenjuju se samo na profil omladinsko.'
            );
        }
    }

    private function assertYear(Competition $competition): void
    {
        if ($competition->year === null) {
            throw new DomainException('Godišnja instanca zahtijeva godinu.');
        }
    }

    private function assertCallNumber(mixed $callNumber, int $expected): void
    {
        $value = $callNumber === null ? null : (int) $callNumber;

        if ($value !== self::CALL_FIRST && $value !== self::CALL_SECOND) {
            throw new DomainException('Treći Poziv nije dozvoljen. Dozvoljeni su samo call_number 1 ili 2.');
        }

        if ($value !== $expected) {
            throw new DomainException(
                $expected === self::CALL_FIRST
                    ? 'Prvi Poziv mora imati call_number 1.'
                    : 'Drugi Poziv mora imati call_number 2.'
            );
        }
    }

    private function requirePositiveDecimal(string $value, string $field): string
    {
        $this->assertBcMath();

        $normalized = $this->normalizeDecimal($value);

        if (bccomp($normalized, '0.00', 2) !== 1) {
            throw new DomainException($field.' mora biti veći od nule.');
        }

        return $normalized;
    }

    private function normalizeDecimal(string $value): string
    {
        $this->assertBcMath();

        $trimmed = trim($value);

        if ($trimmed === '' || ! is_numeric($trimmed)) {
            return '0.00';
        }

        return bcadd($trimmed, '0', 2);
    }

    private function assertBcMath(): void
    {
        if (! extension_loaded('bcmath')) {
            throw new DomainException('Decimalni obračun zahtijeva PHP bcmath.');
        }
    }
}
