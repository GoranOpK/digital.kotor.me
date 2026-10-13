<?php

namespace App\Services\Competitions\Outcome;

use App\Services\Competitions\Profile\CompetitionProfilePolicy;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalSubsidyType;

/**
 * Čl.19 canonical MMSP subsidy caps. Basis is the frozen P2 total investment only.
 *
 *   legal max = min(20% of the Poziv budget, type share × P2 total investment)
 *   type share: authentic local products/services 80%, innovative / digitalization 50%.
 *
 * Decimal-safe (bcmath, 2 decimals). Percentages are truncated to the cent so a cap
 * never exceeds its legal share through rounding.
 */
final class CanonicalAmountCapPolicy
{
    public const SCALE = 2;

    public const AMOUNT_PATTERN = '/^\d{1,12}(?:[.,]\d{1,2})?$/';

    public const AMOUNT_INVALID_MESSAGE = 'Iznos podrške mora biti pozitivan iznos u eurima sa najviše dvije decimale.';

    public const AMOUNT_NOT_POSITIVE_MESSAGE = 'Iznos podrške mora biti veći od nule.';

    public const AMOUNT_ABOVE_CAP_MESSAGE = 'Iznos podrške prelazi zakonski maksimum (čl.19: 20% budžeta Poziva i udio vrste subvencije u ukupnoj vrijednosti ulaganja iz Obrasca P2).';

    public const BASIS_MISSING_MESSAGE = 'Zakonski maksimum se ne može utvrditi: nedostaje zaključan Obrazac P2 sa važećom vrstom subvencije i ukupnom vrijednošću ulaganja.';

    /**
     * Normalized "12345.67" or null for anything that is not a plain non-negative euro amount.
     */
    public function normalizeAmount(mixed $value): ?string
    {
        if (is_int($value)) {
            $value = (string) $value;
        }
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if (preg_match(self::AMOUNT_PATTERN, $value) !== 1) {
            return null;
        }

        return bcadd(str_replace(',', '.', $value), '0', self::SCALE);
    }

    public function budgetCap(string $budget): string
    {
        return $this->percentOf($budget, $this->budgetCapPercent());
    }

    public function investmentCap(string $subsidyType, string $totalInvestment): ?string
    {
        $percent = $this->investmentCapPercent($subsidyType);

        return $percent === null ? null : $this->percentOf($totalInvestment, $percent);
    }

    /**
     * @return string|null null when the frozen P2 basis is missing or invalid (fail closed)
     */
    public function legalMaximum(string $budget, ?string $subsidyType, ?string $totalInvestment): ?string
    {
        if ($subsidyType === null || $totalInvestment === null || bccomp($totalInvestment, '0', self::SCALE) <= 0) {
            return null;
        }

        $investmentCap = $this->investmentCap($subsidyType, $totalInvestment);
        if ($investmentCap === null) {
            return null;
        }

        $budgetCap = $this->budgetCap($budget);

        return bccomp($budgetCap, $investmentCap, self::SCALE) <= 0 ? $budgetCap : $investmentCap;
    }

    /**
     * Validates a proposed supported amount against the legal maximum and, when given, the remaining budget.
     */
    public function supportedAmountBlockReason(?string $amount, ?string $legalMaximum, ?string $remainingBudget = null): ?string
    {
        if ($amount === null) {
            return self::AMOUNT_INVALID_MESSAGE;
        }
        if (bccomp($amount, '0', self::SCALE) <= 0) {
            return self::AMOUNT_NOT_POSITIVE_MESSAGE;
        }
        if ($legalMaximum === null) {
            return self::BASIS_MISSING_MESSAGE;
        }
        if (bccomp($amount, $legalMaximum, self::SCALE) > 0) {
            return self::AMOUNT_ABOVE_CAP_MESSAGE;
        }
        if ($remainingBudget !== null && bccomp($amount, $remainingBudget, self::SCALE) > 0) {
            return CanonicalAllocationPolicy::BUDGET_EXCEEDED_MESSAGE;
        }

        return null;
    }

    public function budgetCapPercent(): int
    {
        $caps = $this->policy()->financialCaps();
        $percent = (int) ($caps[0] ?? 0);
        if ($percent !== CanonicalSubsidyType::BUDGET_CAP_PERCENTAGE) {
            throw new \LogicException('Kanonski čl.19 limit budžeta nije usklađen između profila i vrsta subvencija.');
        }

        return $percent;
    }

    public function investmentCapPercent(string $subsidyType): ?int
    {
        if (! CanonicalSubsidyType::isValid($subsidyType)) {
            return null;
        }

        $percent = $this->policy()->investmentCapPercent($subsidyType);
        if ($percent !== CanonicalSubsidyType::capPercentage($subsidyType)) {
            throw new \LogicException('Kanonski čl.19 udio vrste subvencije nije usklađen između profila i vrsta subvencija.');
        }

        return $percent;
    }

    private function percentOf(string $amount, int $percent): string
    {
        return bcdiv(bcmul($amount, (string) $percent, self::SCALE + 2), '100', self::SCALE);
    }

    private function policy(): CompetitionProfilePolicy
    {
        return CompetitionProfileResolver::resolve(CompetitionProfileResolver::TYPE_CANONICAL);
    }
}
