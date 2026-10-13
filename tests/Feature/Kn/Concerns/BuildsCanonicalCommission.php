<?php

namespace Tests\Feature\Kn\Concerns;

use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Commission\CanonicalCommissionMembershipService;
use Carbon\CarbonImmutable;

trait BuildsCanonicalCommission
{
    protected function canonicalActor(): User
    {
        return User::query()->where('email', 'kn-governance-admin@example.test')->first()
            ?? User::factory()->create([
                'name' => 'Sekretar Skupštine (evidencija)',
                'email' => 'kn-governance-admin@example.test',
                'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
    }

    protected function komisijaUser(string $label): User
    {
        return User::factory()->create([
            'name' => 'Član '.$label,
            'email' => $label.'-'.uniqid().'@example.test',
            'role_id' => Role::where('name', 'komisija')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Governance-valid canonical Commission: predsjednik (1), sekretar (2), spoljašnji/biznis (3),
     * declarations signed, mandate from $mandateStart.
     *
     * @return array{0: Commission, 1: array{predsjednik: CommissionMember, sekretar: CommissionMember, spoljasnji: CommissionMember}}
     */
    protected function canonicalCommission(
        ?User $chair = null,
        string $mandateStart = '2026-01-01 00:00:00',
        bool $signDeclarations = true,
    ): array {
        $service = app(CanonicalCommissionMembershipService::class);
        $actor = $this->canonicalActor();
        $start = CarbonImmutable::parse($mandateStart, 'Europe/Belgrade');

        $commission = $service->establish('Kanonska Komisija '.uniqid(), $start, 'Rješenje br. 01-'.uniqid(), $start);

        $seats = [
            CanonicalCommissionGovernance::ROLE_PREDSJEDNIK => [1, null, $chair ?? $this->komisijaUser('predsjednik')],
            CanonicalCommissionGovernance::ROLE_SEKRETAR => [2, null, $this->komisijaUser('sekretar')],
            CanonicalCommissionGovernance::ROLE_SPOLJASNJI => [3, 'biznis', $this->komisijaUser('spoljasnji')],
        ];

        $members = [];
        foreach ($seats as $role => [$seatNo, $category, $user]) {
            $result = $service->appoint($commission, $user, $seatNo, $role, $category, 'Rješenje '.$role, $start, $actor);
            $this->assertTrue($result->succeeded, (string) $result->error);
            $member = CommissionMember::query()->findOrFail($result->recordId);
            if ($signDeclarations) {
                $this->signDeclarations($member);
            }
            $members[$role] = $member->fresh();
        }

        return [$commission->fresh(), $members];
    }

    protected function signDeclarations(CommissionMember $member): void
    {
        $member->update([
            'confidentiality_declaration' => 'Izjava o povjerljivosti.',
            'conflict_of_interest_declaration' => 'Izjava o nepostojanju sukoba interesa.',
            'declarations_signed_at' => now(),
        ]);
    }
}
