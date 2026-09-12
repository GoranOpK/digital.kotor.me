<?php

namespace App\Identity\Runtime;

use App\Identity\PhoneCallingCodeCatalog;
use App\Models\User;
use App\Security\JmbEncryptedReadService;
use App\Support\UserType;

final class ExistingSubjectIdentityPrefill
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user, string $branch): array
    {
        $phoneSuggestion = PhoneCallingCodeCatalog::suggestionFromStored($user->phone);

        if ($branch === ExistingSubjectIdentityEligibilityResult::BRANCH_DOO) {
            return [
                'branch' => $branch,
                'subject_label' => 'Pravno lice',
                'form_label' => 'DOO — Društvo sa ograničenom odgovornošću',
                'legal_name' => $this->text($user->company_name),
                'pib' => $this->text($user->pib),
                'street_and_number' => $this->text($user->address),
                'city' => $this->text($user->city),
                'phone_calling_code' => $phoneSuggestion['calling_code'] ?? '',
                'phone_national' => $phoneSuggestion['national'] ?? '',
                'first_name' => '',
                'last_name' => '',
                'residential_status' => '',
                'jmb' => '',
                'jmb_hidden' => false,
                'passport_number' => '',
                'id_document_type' => '',
                'residence_country_code' => '',
                'entrepreneur_business_name' => '',
                'crps_number' => '',
                'authorized_first_name' => '',
                'authorized_last_name' => '',
                'authorized_id_document_type' => '',
                'authorized_jmb' => '',
                'authorized_passport_number' => '',
                'authorized_passport_issuing_country_code' => '',
            ];
        }

        if ($branch === ExistingSubjectIdentityEligibilityResult::BRANCH_PHYSICAL_PERSON) {
            return $this->physicalPersonPrefill($user, $branch, $phoneSuggestion);
        }

        $residential = $this->text($user->residential_status);
        $jmb = $this->jmbForUser($user);
        $idDocumentType = '';
        if ($residential === 'resident' && $jmb !== '') {
            $idDocumentType = 'jmb';
        } elseif ($residential === 'non-resident') {
            if ($jmb !== '') {
                $idDocumentType = 'jmb';
            } elseif ($this->text($user->passport_number) !== '') {
                $idDocumentType = 'passport';
            }
        }

        return [
            'branch' => $branch,
            'subject_label' => 'Fizičko lice',
            'form_label' => 'Da',
            'legal_name' => '',
            'first_name' => $this->text($user->first_name),
            'last_name' => $this->text($user->last_name),
            'residential_status' => $residential,
            'id_document_type' => $idDocumentType,
            'jmb' => $jmb,
            'jmb_hidden' => false,
            'passport_number' => $this->text($user->passport_number),
            'residence_country_code' => '',
            'entrepreneur_business_name' => $this->text($user->company_name),
            'pib' => $this->text($user->pib),
            'crps_number' => '',
            'street_and_number' => $this->text($user->address),
            'city' => $this->text($user->city),
            'phone_calling_code' => $phoneSuggestion['calling_code'] ?? '',
            'phone_national' => $phoneSuggestion['national'] ?? '',
            'authorized_first_name' => '',
            'authorized_last_name' => '',
            'authorized_id_document_type' => '',
            'authorized_jmb' => '',
            'authorized_passport_number' => '',
            'authorized_passport_issuing_country_code' => '',
        ];
    }

    public function lockedUserType(User $user): ?string
    {
        return match ($user->user_type) {
            UserType::LIMITED_LIABILITY_COMPANY, UserType::ENTREPRENEUR => $user->user_type,
            default => null,
        };
    }

    /**
     * @param  array{calling_code?: string, national?: string}|null  $phoneSuggestion
     * @return array<string, mixed>
     */
    private function physicalPersonPrefill(User $user, string $branch, ?array $phoneSuggestion): array
    {
        $residential = $this->text($user->residential_status);
        if (! in_array($residential, ['resident', 'non-resident'], true)) {
            $residential = '';
        }

        $jmbHidden = app(ExistingSubjectIdentityStoredJmb::class)->readUsable($user) !== null;

        return [
            'branch' => $branch,
            'subject_label' => 'Fizičko lice',
            'form_label' => '',
            'legal_name' => '',
            'first_name' => $this->text($user->first_name),
            'last_name' => $this->text($user->last_name),
            'residential_status' => $residential,
            'id_document_type' => '',
            'jmb' => '',
            'jmb_hidden' => $jmbHidden,
            'passport_number' => $jmbHidden ? '' : $this->text($user->passport_number),
            'residence_country_code' => '',
            'entrepreneur_business_name' => '',
            'pib' => '',
            'crps_number' => '',
            'street_and_number' => $this->exactText($user->address),
            'city' => $this->cityFromUser($user),
            'phone_calling_code' => $phoneSuggestion['calling_code'] ?? '',
            'phone_national' => $phoneSuggestion['national'] ?? '',
            'authorized_first_name' => '',
            'authorized_last_name' => '',
            'authorized_id_document_type' => '',
            'authorized_jmb' => '',
            'authorized_passport_number' => '',
            'authorized_passport_issuing_country_code' => '',
        ];
    }

    private function cityFromUser(User $user): string
    {
        if (! is_string($user->city) || trim($user->city) === '') {
            return '';
        }

        return $user->city;
    }

    private function exactText(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private function jmbForUser(User $user): string
    {
        $value = app(JmbEncryptedReadService::class)->readValue(
            $user->jmb_encrypted,
            $user->jmb,
            'users',
            $user->id,
            'jmb/jmb_encrypted',
        );

        return $this->text($value);
    }

    private function text(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }
}
