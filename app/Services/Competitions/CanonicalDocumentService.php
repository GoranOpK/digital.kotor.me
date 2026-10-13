<?php

namespace App\Services\Competitions;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\CanonicalApplicantForm;
use App\Models\CanonicalDocumentEvidence;
use App\Models\User;
use App\Services\Competitions\Decision\CanonicalAdministrativeGate;
use App\Support\CanonicalDocumentCatalog;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * KN-SYNC-015 — canonical čl.14 uploads: owner-only, draft-only, within deadline, catalog-only,
 * with the declared alternative and the 30-day issue date recorded next to the document.
 */
final class CanonicalDocumentService
{
    public const UNKNOWN_TYPE_MESSAGE = 'Ovaj tip dokumenta ne pripada listi dokumentacije kanonske prijave za izabranu pravnu formu.';

    public const LOCKED_MESSAGE = 'Prijava je zaključana. Dokumente više nije moguće dodavati.';

    public const DUPLICATE_MESSAGE = 'Ovaj dokument je već priložen.';

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: array{variant: ?string, issued_on: ?string}, 1: array<string, string>}
     */
    public function validateEvidence(Application $application, mixed $documentType, array $input): array
    {
        $catalog = CanonicalDocumentCatalog::forApplication($application);
        $evidence = ['variant' => null, 'issued_on' => null];

        if (! is_string($documentType) || ! $catalog->allowsUploadType($documentType)) {
            return [$evidence, ['document_type' => self::UNKNOWN_TYPE_MESSAGE]];
        }

        $errors = [];
        $variants = $catalog->variantsFor($documentType);
        if ($variants !== []) {
            $variant = is_string($input['document_variant'] ?? null) ? trim($input['document_variant']) : '';
            if (! array_key_exists($variant, $variants)) {
                $errors['document_variant'] = 'Označite koji je dokument priložen: '.implode(' ili ', $variants).'.';
            } else {
                $evidence['variant'] = $variant;
            }
        }

        if ($catalog->requiresIssueDate($documentType)) {
            $raw = is_string($input['issued_on'] ?? null) ? trim($input['issued_on']) : '';
            $parsed = self::strictDate($raw);
            if ($parsed === null) {
                $errors['issued_on'] = 'Unesite datum izdavanja dokumenta (GGGG-MM-DD); dokument ne smije biti stariji od '.CanonicalDocumentCatalog::VALIDITY_DAYS.' dana.';
            } else {
                $issue = $catalog->validityIssue($raw, now());
                if ($issue !== null) {
                    $errors['issued_on'] = 'Datum izdavanja: '.$issue;
                } else {
                    $evidence['issued_on'] = $raw;
                }
            }
        }

        return [$evidence, $errors];
    }

    /**
     * Strict GGGG-MM-DD parse; malformed input is a validation error, never an exception.
     */
    public static function strictDate(string $raw): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return null;
        }

        try {
            $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $raw);
        } catch (\Throwable) {
            return null;
        }

        return $parsed && $parsed->format('Y-m-d') === $raw ? $parsed : null;
    }

    public function uploadBlockReason(Application $application, User $actor): ?string
    {
        if ((int) $application->user_id !== (int) $actor->id) {
            return 'Nemate pristup ovoj prijavi.';
        }
        if (! $application->isCanonicalProfile()) {
            return CanonicalApplicantFormService::NOT_CANONICAL_MESSAGE;
        }
        if (CanonicalAdministrativeGate::isClosed($application->competition)) {
            return CanonicalAdministrativeGate::CLOSED_MESSAGE;
        }
        if ($application->isApplicantContentWriteLocked()) {
            return self::LOCKED_MESSAGE;
        }
        if (CanonicalDocumentCatalog::forApplication($application)->legalForm === null) {
            return CanonicalApplicantFormService::LEGAL_FORM_REQUIRED_MESSAGE;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $attributes  ApplicationDocument storage attributes (name, file_path, cloud_path, ...)
     * @param  array{variant: ?string, issued_on: ?string}  $evidence
     */
    public function attach(Application $application, User $actor, string $documentType, array $attributes, array $evidence): ProcessWriteResult
    {
        return DB::transaction(function () use ($application, $actor, $documentType, $attributes, $evidence) {
            $locked = Application::query()->whereKey($application->id)->lockForUpdate()->first();
            if (! $locked) {
                return ProcessWriteResult::failed('Prijava nije pronađena.');
            }

            $block = $this->uploadBlockReason($locked, $actor);
            if ($block !== null) {
                return ProcessWriteResult::failed($block);
            }

            $catalog = CanonicalDocumentCatalog::forApplication($locked);
            if (! $catalog->allowsUploadType($documentType)) {
                return ProcessWriteResult::failed(self::UNKNOWN_TYPE_MESSAGE);
            }
            if ($locked->documents()->where('document_type', $documentType)->exists()) {
                return ProcessWriteResult::failed(self::DUPLICATE_MESSAGE);
            }

            $document = ApplicationDocument::query()->create(array_merge($attributes, [
                'application_id' => $locked->id,
                'document_type' => $documentType,
                'is_required' => in_array($documentType, $locked->getStrictlyRequiredDocuments(), true),
            ]));

            CanonicalDocumentEvidence::query()->create([
                'application_document_id' => $document->id,
                'application_id' => $locked->id,
                'document_type' => $documentType,
                'variant' => $evidence['variant'] ?? null,
                'issued_on' => $evidence['issued_on'] ?? null,
            ]);

            return ProcessWriteResult::ok((int) $document->id);
        });
    }

    /**
     * Authoritative FINAL document gate at $at (required/conditional items, alternatives, 30-day rule).
     */
    public function finalBlockReason(Application $application, CarbonInterface $at): ?string
    {
        $issues = CanonicalDocumentCatalog::forApplication($application)->finalIssues($application, $at);

        return $issues === [] ? null : 'Prateća dokumentacija (čl.14) nije kompletna: '.implode(' ', $issues);
    }

    /**
     * Applicant/commission checklist rows for the legal form, with the attached document per upload slot.
     *
     * @return list<array<string, mixed>>
     */
    public function checklist(Application $application, CarbonInterface $at): array
    {
        $catalog = CanonicalDocumentCatalog::forApplication($application);
        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->first();
        $documents = $application->documents()->orderBy('id')->get()->keyBy('document_type');
        $evidence = CanonicalDocumentEvidence::query()->where('application_id', $application->id)->get()->keyBy('document_type');
        $required = $catalog->requiredUploadTypes($form, $evidence->all(), $at);

        $rows = [];
        foreach ($catalog->items() as $item) {
            $rows[] = $this->row($catalog, $item, $item['key'], $item['label'], $documents, $evidence, $required, $at);
            if ($item['supplement'] !== null) {
                $rows[] = $this->row(
                    $catalog,
                    array_merge($item, ['variants' => [], 'validity_days' => null, 'condition' => $item['supplement']['condition'], 'requirement' => CanonicalDocumentCatalog::CONDITIONAL]),
                    $item['supplement']['key'],
                    $item['supplement']['label'],
                    $documents,
                    $evidence,
                    $required,
                    $at,
                );
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  \Illuminate\Support\Collection<string, ApplicationDocument>  $documents
     * @param  \Illuminate\Support\Collection<string, CanonicalDocumentEvidence>  $evidence
     * @param  list<string>  $required
     * @return array<string, mixed>
     */
    private function row(CanonicalDocumentCatalog $catalog, array $item, string $key, string $label, $documents, $evidence, array $required, CarbonInterface $at): array
    {
        $document = $item['source'] === CanonicalDocumentCatalog::SOURCE_UPLOAD ? $documents->get($key) : null;
        $row = $evidence->get($key);

        return [
            'item' => $item['item'],
            'key' => $key,
            'label' => $label,
            'source' => $item['source'],
            'requirement' => $item['requirement'],
            'condition' => $item['condition'],
            'variants' => $item['variants'],
            'needs_issue_date' => $item['validity_days'] !== null,
            'required_now' => $item['source'] === CanonicalDocumentCatalog::SOURCE_UPLOAD && in_array($key, $required, true),
            'document' => $document,
            'variant' => $row?->variant,
            'variant_label' => $row?->variant !== null ? ($item['variants'][$row->variant] ?? $row->variant) : null,
            'issued_on' => $row?->issued_on?->toDateString(),
            'validity_issue' => $document !== null && $item['validity_days'] !== null
                ? $catalog->validityIssue($row?->issued_on, $at)
                : null,
        ];
    }
}
