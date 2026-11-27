<?php

namespace App\Support;

use App\Models\Application;
use App\Models\ApplicationCompletenessAssessment;

/**
 * Prikaz Obrasca P3. Izolovano od mladih i ženskog programa:
 * ne dira EliminatoryProfileConfig, memorandum M3 niti njihove tekstove.
 */
final class MmspP3Form
{
    public const FORM_TITLE = 'LISTA ZA OCJENJIVANJE PLANOVA ULAGANJA';

    public const FORM_SUBTITLE = '(Popunjava Komisija za dodjelu subvencija za preduzetnike i MMSP)';

    public const NE_STAR_FOOTNOTE = '* Odgovor \'Ne\' predstavlja eliminatorni razlog. U slučaju utvrđenog najmanje jednog eliminatornog razloga, plan ulaganja se ne boduje, osim ako se u postupku po prigovoru otklone svi utvrđeni eliminatorni razlozi.';

    public const FAIL_CONFIRMATION_MESSAGE = 'Prijava ne ispunjava jedan ili više eliminatornih kriterijuma. Da li želite da nastavite?';

    public const SCORE_CONFIRMATION_MESSAGE = 'Ovim potvrđujete unesene ocjene i napomenu. Nakon potvrde individualno bodovanje ove prijave više nije moguće mijenjati. Da li želite da završite bodovanje?';

    public const RESULT_PENDING = 'Nije potvrđeno';

    public const RESULT_PASS = 'Ispunjava eliminatorne kriterijume';

    public const RESULT_FAIL = 'Ne ispunjava eliminatorne kriterijume';

    /**
     * Postojeće MMSP formulacije. Redoslijed i fail_phrase ostaju oni koje
     * evidencija potpunosti već upisuje u obrazloženje i obavještenje.
     *
     * @var array<string, array{field: string, statement: string, fail_phrase: string}>
     */
    public const CRITERIA = [
        'documentation' => [
            'field' => 'criterion_documentation',
            'statement' => 'Dokumentacija je potpuna',
            'fail_phrase' => 'dokumentacija nije potpuna',
        ],
        'report' => [
            'field' => 'criterion_report',
            'statement' => 'Dostavljen je izvještaj o prethodnoj realizaciji, ako je obavezan',
            'fail_phrase' => 'nije dostavljen obavezan izvještaj o prethodnoj realizaciji',
        ],
        'related' => [
            'field' => 'criterion_related',
            'statement' => 'Plan ulaganja se odnosi na vrstu subvencije',
            'fail_phrase' => 'plan ulaganja se ne odnosi na vrstu subvencije',
        ],
    ];

    public static function subjectName(Application $application): string
    {
        $application->loadMissing('canonicalApplicantForm');
        $form = $application->canonicalApplicantForm;
        if ($form === null || $form->frozen_at === null) {
            return '—';
        }

        $value = match ($form->legal_form) {
            CanonicalLegalForm::PREDUZETNIK => $form->person_name,
            CanonicalLegalForm::DRUSTVO => $form->company_name,
            default => null,
        };
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : '—';
    }

    public static function subsidyLabel(Application $application): string
    {
        $application->loadMissing(['finalClaim', 'canonicalInvestmentPlan', 'canonicalApplicantForm']);

        $candidates = [
            $application->finalClaim?->subsidy_type,
            $application->canonicalInvestmentPlan?->frozen_at !== null
                ? $application->canonicalInvestmentPlan?->subsidy_type
                : null,
            $application->canonicalApplicantForm?->frozen_at !== null
                ? $application->canonicalApplicantForm?->subsidy_type
                : null,
        ];

        foreach ($candidates as $code) {
            if (CanonicalSubsidyType::isValid($code)) {
                return CanonicalSubsidyType::label($code);
            }
        }

        return '—';
    }

    /**
     * @return array{recorded: bool, answers: array<string, string>, explanation: string, result: string}
     */
    public static function administrativePresentation(Application $application): array
    {
        $rows = ApplicationCompletenessAssessment::query()
            ->where('application_id', $application->id)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'recorded' => false,
                'answers' => self::unmarkedAnswers(),
                'explanation' => '',
                'result' => self::RESULT_PENDING,
            ];
        }

        /** @var ApplicationCompletenessAssessment $first */
        $first = $rows->first();
        /** @var ApplicationCompletenessAssessment $latest */
        $latest = $rows->last();
        $source = $rows->first(
            fn (ApplicationCompletenessAssessment $row) => is_array($row->criterion_answers) && $row->criterion_answers !== []
        ) ?? $first;

        return [
            'recorded' => true,
            'answers' => self::answersFor($source),
            'explanation' => trim((string) $first->notes),
            'result' => $latest->outcome === ApplicationCompletenessAssessment::COMPLETE
                ? self::RESULT_PASS
                : self::RESULT_FAIL,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function answersFor(ApplicationCompletenessAssessment $assessment): array
    {
        $stored = $assessment->criterion_answers;
        if (is_array($stored)) {
            $mapped = [];
            foreach (self::CRITERIA as $key => $criterion) {
                $value = $stored[$key] ?? null;
                if ($value === 'da') {
                    $mapped[$key] = 'Da';
                } elseif ($value === 'ne') {
                    $mapped[$key] = 'Ne*';
                } else {
                    $mapped = [];
                    break;
                }
            }
            if ($mapped !== []) {
                return $mapped;
            }
        }

        $notes = (string) $assessment->notes;
        if (preg_match('/^Nije ispunjeno:\s*([^\n]*)/u', $notes, $matches) === 1) {
            $text = $matches[1];
            $mapped = [];
            foreach (self::CRITERIA as $key => $criterion) {
                $mapped[$key] = str_contains($text, $criterion['fail_phrase']) ? 'Ne*' : 'Da';
            }

            return $mapped;
        }

        return self::unmarkedAnswers();
    }

    /**
     * @return array<string, string>
     */
    private static function unmarkedAnswers(): array
    {
        $answers = [];
        foreach (self::CRITERIA as $key => $criterion) {
            $answers[$key] = 'Nije označeno';
        }

        return $answers;
    }
}
