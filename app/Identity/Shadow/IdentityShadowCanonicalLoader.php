<?php

namespace App\Identity\Shadow;

use App\Identity\IdentitySnapshot;
use App\Identity\PhysicalPersonSnapshot;
use App\Models\ForeignBranchIdentity;
use App\Models\ForeignBranchRepresentative;
use App\Models\LegalEntityAuthorizedPerson;
use App\Models\LegalEntityIdentity;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Security\JmbEncryptedReadService;
use Illuminate\Support\Collection;

/**
 * Loads canonical graphs on an explicit connection. Never uses default mysql relations.
 */
class IdentityShadowCanonicalLoader
{
    /**
     * @param  list<int>  $userIds
     * @return array<int, array<string, mixed>>
     */
    public function loadGraphsForUserIds(string $connection, array $userIds): array
    {
        $graphs = [];
        foreach ($userIds as $userId) {
            $graphs[$userId] = $this->emptyGraph();
        }

        if ($userIds === []) {
            return $graphs;
        }

        $platforms = PlatformIdentity::on($connection)
            ->whereIn('user_id', $userIds)
            ->orderBy('id')
            ->get();

        foreach ($platforms as $platform) {
            $platform->setConnection($connection);
        }

        $byUser = $platforms->groupBy(static fn (PlatformIdentity $platform): int => (int) $platform->user_id);
        $platformIds = $platforms->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $flByPlatform = $this->loadByPlatform($connection, PhysicalPersonIdentity::class, $platformIds);
        $leByPlatform = $this->loadByPlatform($connection, LegalEntityIdentity::class, $platformIds);
        $fbByPlatform = $this->loadByPlatform($connection, ForeignBranchIdentity::class, $platformIds);

        $legalEntityIds = $leByPlatform->flatten()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $foreignBranchIds = $fbByPlatform->flatten()->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        $apByLegalEntity = $this->loadChildren($connection, LegalEntityAuthorizedPerson::class, 'legal_entity_identity_id', $legalEntityIds);
        $repByForeignBranch = $this->loadChildren($connection, ForeignBranchRepresentative::class, 'foreign_branch_identity_id', $foreignBranchIds);

        foreach ($userIds as $userId) {
            $userPlatforms = $byUser->get($userId, collect());
            $graphs[$userId] = $this->inspectGraph(
                $userPlatforms,
                $flByPlatform,
                $leByPlatform,
                $fbByPlatform,
                $apByLegalEntity,
                $repByForeignBranch,
            );
        }

        return $graphs;
    }

    /**
     * @param  array<string, mixed>  $graph
     */
    public function snapshotFromValidStep4Graph(array $graph): IdentitySnapshot
    {
        if (($graph['kind'] ?? null) !== 'valid_step4_fl') {
            throw new IdentityShadowException('Canonical snapshot requested for a graph that is not a valid Step 4 physical person.');
        }

        /** @var PlatformIdentity $platform */
        $platform = $graph['platform'];
        /** @var PhysicalPersonIdentity $fl */
        $fl = $graph['physical_person'];

        return new IdentitySnapshot(
            userId: (int) $platform->user_id,
            isRegisteredSubject: true,
            subjectType: $platform->subject_type,
            mobilePhone: $platform->mobile_phone,
            streetAndNumber: $fl->street_and_number,
            city: $fl->city,
            physicalPerson: new PhysicalPersonSnapshot(
                firstName: (string) $fl->first_name,
                lastName: (string) $fl->last_name,
                residentialStatus: (string) $fl->residential_status,
                streetAndNumber: (string) $fl->street_and_number,
                city: (string) $fl->city,
                idDocumentType: $fl->id_document_type,
                jmb: app(JmbEncryptedReadService::class)->readValue(
                    $fl->jmb_encrypted,
                    $fl->jmb,
                    'physical_person_identities',
                    $fl->id,
                    'jmb/jmb_encrypted',
                ),
                passportNumber: $fl->passport_number,
                residenceCountryCode: $fl->residence_country_code,
                isEntrepreneur: (bool) $fl->is_entrepreneur,
                entrepreneurBusinessName: $fl->entrepreneur_business_name,
                pib: $fl->pib,
                crpsNumber: $fl->crps_number,
            ),
        );
    }

    /**
     * @param  list<int>  $platformIds
     * @return Collection<string, Collection<int, mixed>>
     */
    private function loadByPlatform(string $connection, string $modelClass, array $platformIds): Collection
    {
        if ($platformIds === []) {
            return collect();
        }

        $rows = $modelClass::on($connection)
            ->whereIn('platform_identity_id', $platformIds)
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $row->setConnection($connection);
        }

        return $rows->groupBy(static fn ($row): int => (int) $row->platform_identity_id);
    }

    /**
     * @param  list<int>  $parentIds
     * @return Collection<string, Collection<int, mixed>>
     */
    private function loadChildren(string $connection, string $modelClass, string $foreignKey, array $parentIds): Collection
    {
        if ($parentIds === []) {
            return collect();
        }

        $rows = $modelClass::on($connection)
            ->whereIn($foreignKey, $parentIds)
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $row->setConnection($connection);
        }

        return $rows->groupBy(static fn ($row): int => (int) $row->{$foreignKey});
    }

    /**
     * @param  Collection<int, PlatformIdentity>  $platforms
     * @param  Collection<string, Collection<int, mixed>>  $flByPlatform
     * @param  Collection<string, Collection<int, mixed>>  $leByPlatform
     * @param  Collection<string, Collection<int, mixed>>  $fbByPlatform
     * @param  Collection<string, Collection<int, mixed>>  $apByLegalEntity
     * @param  Collection<string, Collection<int, mixed>>  $repByForeignBranch
     * @return array<string, mixed>
     */
    private function inspectGraph(
        Collection $platforms,
        Collection $flByPlatform,
        Collection $leByPlatform,
        Collection $fbByPlatform,
        Collection $apByLegalEntity,
        Collection $repByForeignBranch,
    ): array {
        if ($platforms->isEmpty()) {
            return $this->emptyGraph();
        }

        if ($platforms->count() !== 1) {
            return $this->graphResult('invalid', ['multiple_platform_identities']);
        }

        /** @var PlatformIdentity $platform */
        $platform = $platforms->first();
        $platformId = (int) $platform->id;
        $flRows = $flByPlatform->get($platformId, collect());
        $leRows = $leByPlatform->get($platformId, collect());
        $fbRows = $fbByPlatform->get($platformId, collect());

        $apCount = 0;
        foreach ($leRows as $legalEntity) {
            $apCount += $apByLegalEntity->get((int) $legalEntity->id, collect())->count();
        }
        $repCount = 0;
        foreach ($fbRows as $foreignBranch) {
            $repCount += $repByForeignBranch->get((int) $foreignBranch->id, collect())->count();
        }

        $flCount = $flRows->count();
        $leCount = $leRows->count();
        $fbCount = $fbRows->count();
        $branchCount = (int) ($flCount > 0) + (int) ($leCount > 0) + (int) ($fbCount > 0);

        if ($branchCount === 0) {
            return $this->graphResult('invalid', ['missing_subtype'], $platform);
        }

        if ($branchCount > 1 || $flCount > 1 || $leCount > 1 || $fbCount > 1) {
            return $this->graphResult('invalid', ['multiple_branches'], $platform);
        }

        if ($flCount === 1) {
            if ($platform->subject_type !== PlatformIdentity::SUBJECT_PHYSICAL_PERSON) {
                return $this->graphResult('invalid', ['subject_type_mismatch'], $platform, $flRows->first());
            }

            if ($apCount > 0 || $repCount > 0) {
                return $this->graphResult('invalid', ['inconsistent_child'], $platform, $flRows->first());
            }

            return [
                'kind' => 'valid_step4_fl',
                'reason_codes' => [],
                'platform' => $platform,
                'physical_person' => $flRows->first(),
            ];
        }

        return $this->graphResult('invalid', ['unexpected_non_physical_person_graph'], $platform);
    }

    /**
     * @param  list<string>  $reasonCodes
     * @return array<string, mixed>
     */
    private function graphResult(
        string $kind,
        array $reasonCodes,
        ?PlatformIdentity $platform = null,
        mixed $physicalPerson = null,
    ): array {
        return [
            'kind' => $kind,
            'reason_codes' => $reasonCodes,
            'platform' => $platform,
            'physical_person' => $physicalPerson,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyGraph(): array
    {
        return $this->graphResult('none', []);
    }
}
