<?php

namespace App\Support;

use App\Models\ApplicationEliminatoryCheck;
use App\Services\Competitions\Lifecycle\LegacyCommissionFlowGuard;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use DomainException;

/**
 * Profilna konfiguracija Obrasca 3 (eliminatorna provjera).
 *
 * Boolean polaritet kolona `criterion_1..3` je isti za oba profila i ne smije se okrenuti:
 * - `true`  = prolaz; eliminatorni razlog NIJE aktiviran;
 * - `false` = pad; eliminatorni razlog JESTE aktiviran.
 *
 * UI labele moraju pratiti taj polaritet: vrijednost `1` nikada ne znači aktivirani razlog.
 */
final class EliminatoryProfileConfig
{
    public const YOUTH_NOTE_PREFIX = "OM-M3-NOTES-v1\n";

    public const YOUTH_EXPLANATION_REQUIRED_MESSAGE = 'Obrazloženje je obavezno kada je eliminatorni razlog aktiviran.';

    public const NOTE_REQUIRED_ON_FAIL_MESSAGE = 'Napomena je obavezna kada postoji najmanje jedan odgovor Ne*.';

    public const YOUTH_FAIL_CONFIRMATION_MESSAGE = 'Prijava ne ispunjava jedan ili više eliminatornih kriterijuma. Da li želite da nastavite?';

    /**
     * @param  array<int, array{statement: string, pass_label: string, fail_label: string}>  $criteria
     */
    private function __construct(
        public readonly ?string $type,
        public readonly string $formSubtitle,
        public readonly bool $usesStructuredNotes,
        public readonly bool $scoringUnlockedAfterPass,
        public readonly bool $sendsFailNotice,
        public readonly string $failConfirmationMessage,
        public readonly string $passLabel,
        public readonly string $failLabel,
        public readonly array $criteria,
    ) {}

    public static function for(?string $type): self
    {
        if (! CompetitionProfileResolver::resolve($type)->allowsLegacyCommissionFlows()) {
            throw new DomainException(LegacyCommissionFlowGuard::BLOCKED_MESSAGE);
        }

        return match ($type) {
            'omladinsko' => new self(
                type: 'omladinsko',
                formSubtitle: '(Popunjava Komisija za podršku preduzetništvu mladih)',
                usesStructuredNotes: true,
                scoringUnlockedAfterPass: false,
                sendsFailNotice: true,
                failConfirmationMessage: self::YOUTH_FAIL_CONFIRMATION_MESSAGE,
                passLabel: 'Da',
                failLabel: 'Ne*',
                criteria: [
                    1 => [
                        'statement' => 'Dostavljena je potpuna dokumentacija?',
                        'pass_label' => 'Da',
                        'fail_label' => 'Ne*',
                    ],
                    2 => [
                        'statement' => 'Ako je podnosilac ranije koristio podršku, da li je dostavio M4/M4a?',
                        'pass_label' => 'Da',
                        'fail_label' => 'Ne*',
                    ],
                    3 => [
                        'statement' => 'Biznis plan je povezan sa prioritetnim oblastima iz člana 12 Odluke o mladima?',
                        'pass_label' => 'Da',
                        'fail_label' => 'Ne*',
                    ],
                ],
            ),
            default => new self(
                type: $type,
                formSubtitle: '(Popunjava Komisija za raspodjelu sredstava za podršku ženskom preduzetništvu)',
                usesStructuredNotes: false,
                scoringUnlockedAfterPass: true,
                sendsFailNotice: true,
                failConfirmationMessage: 'Prijava ne ispunjava jedan ili više eliminatornih kriterijuma i biće odbijena. Da li želite da nastavite?',
                passLabel: 'Da',
                failLabel: 'Ne*',
                criteria: [
                    1 => [
                        'statement' => 'Dostavljena su sva potrebna dokumenta?',
                        'pass_label' => 'Da',
                        'fail_label' => 'Ne*',
                    ],
                    2 => [
                        'statement' => 'Dostavljen je Izvještaj o realizaciji biznis plana sa Finansijskim izvještajem (Obrasci 4 i 4a) i pratećom dokumentacijom (fakture i izvodi sa banke) za biznis plan koji je u prethodnom periodu finansiran ili djelimično finansiran iz budžeta Opštine?',
                        'pass_label' => 'Da',
                        'fail_label' => 'Ne*',
                    ],
                    3 => [
                        'statement' => 'Biznis plan je vezan za prioritetne oblasti navedene u članu 11 Odluke?',
                        'pass_label' => 'Da',
                        'fail_label' => 'Ne*',
                    ],
                ],
            ),
        };
    }

    public function statement(int $number): string
    {
        return $this->criteria[$number]['statement'] ?? '';
    }

    public function optionPassLabel(int $number): string
    {
        return $this->criteria[$number]['pass_label'] ?? $this->passLabel;
    }

    public function optionFailLabel(int $number): string
    {
        return $this->criteria[$number]['fail_label'] ?? $this->failLabel;
    }

    public function displayValue(int $number, mixed $stored, bool $isTrue, bool $isFalse): string
    {
        if ($isTrue) {
            return $this->optionPassLabel($number);
        }
        if ($isFalse) {
            return $this->optionFailLabel($number);
        }

        return 'Nije označeno';
    }

    /**
     * @param  array<int|string, string|null>  $explanationsByNumber
     */
    public static function composeYouthNotes(array $explanationsByNumber): string
    {
        $payload = [];
        foreach ([1, 2, 3] as $number) {
            $payload[(string) $number] = trim((string) ($explanationsByNumber[$number] ?? $explanationsByNumber[(string) $number] ?? ''));
        }

        return self::YOUTH_NOTE_PREFIX.json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Shared Obrazac 3 napomena: copy onto every activated criterion.
     * If none is activated, keep the optional note on criterion 1 so it stays readable.
     *
     * @param  array<int, bool>  $passedByNumber
     */
    public static function composeYouthNotesFromShared(string $sharedNote, array $passedByNumber): string
    {
        $shared = trim($sharedNote);
        $payload = [1 => '', 2 => '', 3 => ''];
        $anyFail = false;

        foreach ([1, 2, 3] as $number) {
            $passed = (bool) ($passedByNumber[$number] ?? true);
            if (! $passed) {
                $anyFail = true;
                $payload[$number] = $shared;
            }
        }

        if (! $anyFail) {
            $payload[1] = $shared;
        }

        return self::composeYouthNotes($payload);
    }

    /**
     * One textarea value for the youth form. Distinct legacy per-criterion notes stay readable.
     */
    public static function youthSharedNote(?string $note): string
    {
        $parsed = self::parseYouthNotes($note);
        $unique = [];
        foreach ([1, 2, 3] as $number) {
            $text = $parsed[$number];
            if ($text !== '' && ! in_array($text, $unique, true)) {
                $unique[] = $text;
            }
        }

        if ($unique !== []) {
            return implode("\n\n", $unique);
        }

        if (is_string($note) && $note !== '' && ! str_starts_with($note, self::YOUTH_NOTE_PREFIX)) {
            return trim($note);
        }

        return '';
    }

    /**
     * @return array{1: string, 2: string, 3: string}
     */
    public static function parseYouthNotes(?string $note): array
    {
        $empty = [1 => '', 2 => '', 3 => ''];

        if (! is_string($note) || ! str_starts_with($note, self::YOUTH_NOTE_PREFIX)) {
            return $empty;
        }

        $decoded = json_decode(substr($note, strlen(self::YOUTH_NOTE_PREFIX)), true);
        if (! is_array($decoded)) {
            return $empty;
        }

        return [
            1 => trim((string) ($decoded['1'] ?? $decoded[1] ?? '')),
            2 => trim((string) ($decoded['2'] ?? $decoded[2] ?? '')),
            3 => trim((string) ($decoded['3'] ?? $decoded[3] ?? '')),
        ];
    }

    public static function youthExplanation(?string $note, int $number): string
    {
        return self::parseYouthNotes($note)[$number] ?? '';
    }

    /**
     * Nacrt: sačuvana obrazloženja po kriterijumu, uključujući prazna.
     *
     * @return array{1: string, 2: string, 3: string}
     */
    public static function youthDraftExplanations(?string $note): array
    {
        return self::parseYouthNotes($note);
    }

    public function commissionName(): string
    {
        return $this->type === 'omladinsko'
            ? 'Komisija za podršku preduzetništvu mladih'
            : 'Komisija za raspodjelu sredstava za podršku ženskom preduzetništvu';
    }

    /**
     * @return list<array{number: int, statement: string, explanation: string}>
     */
    public function activatedReasonDetails(?ApplicationEliminatoryCheck $check): array
    {
        if ($check === null) {
            return [];
        }

        $explanations = $this->usesStructuredNotes
            ? self::parseYouthNotes($check->note)
            : [1 => (string) $check->note, 2 => '', 3 => ''];

        $details = [];
        foreach ([1, 2, 3] as $number) {
            if (! $check->criterionIsFalse($check->{"criterion_{$number}"})) {
                continue;
            }

            $details[] = [
                'number' => $number,
                'statement' => $this->statement($number),
                'explanation' => $this->usesStructuredNotes
                    ? ($explanations[$number] ?? '')
                    : trim((string) $check->note),
            ];
        }

        return $details;
    }

    public static function humanYouthActivatedExplanations(ApplicationEliminatoryCheck $check): string
    {
        $profile = $check->profileConfig();
        $blocks = [];
        foreach ($profile->activatedReasonDetails($check) as $detail) {
            $block = $detail['statement'];
            if ($detail['explanation'] !== '') {
                $block .= "\nObrazloženje Komisije: ".$detail['explanation'];
            }
            $blocks[] = $block;
        }

        return implode("\n\n", $blocks);
    }
}
