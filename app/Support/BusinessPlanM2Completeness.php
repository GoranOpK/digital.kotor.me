<?php

namespace App\Support;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Security\JmbEncryptedReadException;

/**
 * Popunjenost Obrasca M2 za PM i ŽP.
 * Obavezna polja su samo ona označena zvjezdicom.
 * Potpuna popunjenost gleda prikazana polja i služi dugmetu i napomeni.
 */
class BusinessPlanM2Completeness
{
    public const SUBMIT_BLOCKED_MESSAGE = 'Konačno podnošenje nije dozvoljeno dok nijesu popunjena sva obavezna polja biznis plana označena zvjezdicom.';

    public const SAVED_NOTICE = 'Biznis plan je sačuvan. Prije podnošenja prijave provjerite tačnost unesenih podataka.';

    private const FUNDING_ALTERNATIVE = ['vlastita', 'pozajmljena', 'kredit', 'ostali', 'ne_znam'];

    private const FIXED_JOB_PARTS = ['Marketing', 'Poslovanje', 'Finansije'];

    public static function hasRequiredAsterisks(BusinessPlan $plan): bool
    {
        return self::missingRequiredAsterisks($plan) === [];
    }

    /**
     * @return list<string>
     */
    public static function missingRequiredAsterisks(BusinessPlan $plan): array
    {
        $missing = [];

        foreach ([
            'business_idea_name',
            'applicant_name',
            'applicant_address',
            'applicant_phone',
            'applicant_email',
        ] as $field) {
            if (! self::present($plan->{$field})) {
                $missing[] = $field;
            }
        }

        try {
            if (! self::present($plan->applicantJmbgForRead())) {
                $missing[] = 'applicant_jmbg';
            }
        } catch (JmbEncryptedReadException) {
            $missing[] = 'applicant_jmbg';
        }

        if (! $plan->finances_notice_confirmed) {
            $missing[] = 'finances_notice_confirmed';
        }

        return $missing;
    }

    public static function isFullyFilled(BusinessPlan $plan, ?Application $application = null): bool
    {
        if (! self::hasRequiredAsterisks($plan)) {
            return false;
        }

        $application ??= $plan->application;
        $registered = (bool) ($application?->is_registered ?? false);
        $isPm = $application?->competition?->type === 'omladinsko';

        foreach ([
            'promotion',
            'required_resources',
            'work_experience',
            'personal_strengths_weaknesses',
            'biggest_support',
            'annual_sales_volume',
            'annual_purchases_volume',
            'required_amount',
            'requested_amount',
        ] as $field) {
            if (! self::present($plan->{$field})) {
                return false;
            }
        }

        if (! in_array(trim((string) $plan->funding_alternative), self::FUNDING_ALTERNATIVE, true)) {
            return false;
        }

        [$competitionPart1, $competitionPart2] = self::competitionParts($plan->competition_analysis);
        if (! self::present($competitionPart1) || ! self::present($competitionPart2)) {
            return false;
        }

        if ($isPm && trim((string) $plan->realization_type) === KnOmladinskoDocumentPackage::REALIZATION_DRUGO
            && ! self::present($plan->product_service)) {
            return false;
        }

        foreach ([
            'products_services_table',
            'target_customers',
            'sales_locations',
            'pricing_table',
            'revenue_share_table',
            'employment_structure',
            'suppliers_table',
            'funding_sources_table',
            'revenue_projection',
            'risk_matrix',
        ] as $table) {
            if (! self::tableAnswered($plan->{$table})) {
                return false;
            }
        }

        if (! self::expenseCategoryAnswered($plan->expense_projection, 'investment')
            || ! self::expenseCategoryAnswered($plan->expense_projection, 'operating')) {
            return false;
        }

        if (! self::jobScheduleFilled($plan->job_schedule)) {
            return false;
        }

        if ($registered) {
            foreach ([
                'registration_form',
                'company_name',
                'pib',
                'company_address',
                'company_phone',
                'company_email',
                'company_website',
                'bank_account',
            ] as $field) {
                if (! self::present($plan->{$field})) {
                    return false;
                }
            }

            if (! self::present($plan->business_analysis) || ! self::tableAnswered($plan->business_history)) {
                return false;
            }
        }

        return true;
    }

    private static function present(mixed $value): bool
    {
        if ($value === null || is_bool($value)) {
            return $value === true || $value === false;
        }

        if (is_int($value) || is_float($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return false;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function competitionParts(mixed $value): array
    {
        $text = is_string($value) ? $value : '';
        $parts = preg_split("/\n\n---\n\n/", $text, 2);

        return [trim((string) ($parts[0] ?? '')), trim((string) ($parts[1] ?? ''))];
    }

    private static function tableAnswered(mixed $table): bool
    {
        if (! is_array($table)) {
            return false;
        }

        foreach ($table as $row) {
            if (is_array($row) && self::rowHasAnswer($row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $ignore
     */
    private static function rowHasAnswer(array $row, array $ignore = []): bool
    {
        foreach ($row as $key => $value) {
            if (in_array((string) $key, $ignore, true) || is_array($value)) {
                continue;
            }

            if (self::present($value)) {
                return true;
            }
        }

        return false;
    }

    private static function expenseCategoryAnswered(mixed $table, string $category): bool
    {
        if (! is_array($table)) {
            return false;
        }

        foreach ($table as $row) {
            if (! is_array($row) || ($row['category'] ?? null) !== $category) {
                continue;
            }

            $answer = $row;
            unset($answer['category']);
            if (self::rowHasAnswer($answer)) {
                return true;
            }
        }

        return false;
    }

    private static function jobScheduleFilled(mixed $table): bool
    {
        if (! is_array($table)) {
            return false;
        }

        $fixed = [];
        foreach ($table as $row) {
            if (! is_array($row)) {
                continue;
            }

            $part = trim((string) ($row['part'] ?? ''));
            if (in_array($part, self::FIXED_JOB_PARTS, true)) {
                $fixed[$part] = $row;
                continue;
            }

            if (! self::rowHasAnswer($row)) {
                continue;
            }

            if (! self::present($row['part'] ?? null)
                || ! self::present($row['initially'] ?? null)
                || ! self::present($row['later'] ?? null)) {
                return false;
            }
        }

        foreach (self::FIXED_JOB_PARTS as $part) {
            $row = $fixed[$part] ?? null;
            if (! is_array($row)
                || ! self::present($row['initially'] ?? null)
                || ! self::present($row['later'] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
