<?php

namespace App\Services\Competitions;

use App\Models\CanonicalAdministrativeEvent;
use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Decision\CanonicalAdministrativeGate;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Illuminate\Support\Facades\DB;

/**
 * Irreversible close of the application window for one canonical MMSP draft in test preview.
 * Does not publish the call, change its status or dates, or close the competition itself.
 */
final class CanonicalTestApplicationPhaseService
{
    public const NOT_PREVIEW_MESSAGE = 'Zatvaranje testne prijavne faze dostupno je samo za kanonski MMSP nacrt sa uključenim testnim preview režimom.';

    public const UNAUTHORIZED_MESSAGE = 'Testnu prijavnu fazu može zatvoriti samo ovlašćeni administrator ili predsjednik Komisije.';

    public const ALREADY_CLOSED_MESSAGE = 'Testna prijavna faza je već zatvorena. Ponovno otvaranje nije moguće.';

    public const CONFIRMATION_REQUIRED_MESSAGE = 'Potvrdite da je zatvaranje testne prijavne faze nepovratno i da nove prijave više neće biti moguće.';

    public const WINDOW_CLOSED_MESSAGE = 'Testna prijavna faza je zatvorena. Nova prijava, izmjena nacrta i konačna predaja više nisu moguće.';

    public const PREVIEW_LOCKED_MESSAGE = 'Testna prijavna faza je zatvorena. Status Nacrt i testni preview ne mogu se ukloniti, niti se prijave mogu ponovo otvoriti.';

    public const DATES_LOCKED_MESSAGE = 'Testna prijavna faza je zatvorena. Datum početka i rok za prijave ostaju neizmijenjeni.';

    public const PUBLICATION_BLOCKED_MESSAGE = 'Službena objava nije moguća dok je testna prijavna faza zatvorena. Godišnji slot objave ostaje neiskorišćen.';

    /** @var list<string> */
    private const ADMIN_ROLES = ['admin', 'superadmin', 'konkurs_admin'];

    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
        private readonly CanonicalAdministrativeGate $gate,
    ) {
    }

    public function close(Competition $competition, User $actor, bool $confirmed): ProcessWriteResult
    {
        if (! $competition->isCanonicalTestPreview()) {
            return ProcessWriteResult::failed(self::NOT_PREVIEW_MESSAGE);
        }

        $actorReason = $this->actorBlockReason($actor, $competition);
        if ($actorReason !== null) {
            return ProcessWriteResult::failed($actorReason);
        }

        if (! $confirmed) {
            return ProcessWriteResult::failed(self::CONFIRMATION_REQUIRED_MESSAGE);
        }

        return DB::transaction(function () use ($competition, $actor) {
            $locked = Competition::query()->whereKey($competition->id)->lockForUpdate()->first();
            if ($locked === null || ! $locked->isCanonicalTestPreview()) {
                return ProcessWriteResult::failed(self::NOT_PREVIEW_MESSAGE);
            }

            $actorReason = $this->actorBlockReason($actor, $locked);
            if ($actorReason !== null) {
                return ProcessWriteResult::failed($actorReason);
            }

            if ($locked->canonical_test_applications_closed_at !== null) {
                return ProcessWriteResult::failed(self::ALREADY_CLOSED_MESSAGE);
            }

            $closedAt = now();
            $locked->forceFill([
                'canonical_test_applications_closed_at' => $closedAt,
            ])->save();

            $event = $this->gate->audit($locked, CanonicalAdministrativeEvent::TEST_APPLICATIONS_CLOSED, $actor, $locked, [
                'canonical_test_applications_closed_at' => $closedAt->toIso8601String(),
            ]);

            return ProcessWriteResult::ok($event->id);
        });
    }

    /**
     * @return array{closed: bool, closedAt: mixed, closedBy: ?string, canClose: bool, block: ?string}
     */
    public function presentation(Competition $competition, User $viewer): array
    {
        $event = CanonicalAdministrativeEvent::query()
            ->where('competition_id', $competition->id)
            ->where('action', CanonicalAdministrativeEvent::TEST_APPLICATIONS_CLOSED)
            ->orderByDesc('id')
            ->first();
        $closedBy = $event !== null
            ? User::query()->whereKey($event->actor_user_id)->value('name')
            : null;
        $closed = $competition->isCanonicalTestApplicationPhaseClosed();
        $block = $closed ? null : $this->actorBlockReason($viewer, $competition);

        return [
            'closed' => $closed,
            'closedAt' => $competition->canonical_test_applications_closed_at,
            'closedBy' => $closedBy,
            'canClose' => ! $closed && $block === null,
            'block' => $block,
        ];
    }

    /**
     * Keeps a closed test call from leaving draft preview through the generic edit form.
     *
     * @param  array<string, mixed>  $validated
     */
    public function editLeavesClosedPreview(Competition $competition, array $validated, bool $requestedPreview): bool
    {
        if ($competition->canonical_test_applications_closed_at === null) {
            return false;
        }

        return ($validated['status'] ?? null) !== 'draft'
            || ($validated['type'] ?? null) !== CompetitionProfileResolver::TYPE_CANONICAL
            || ! $requestedPreview;
    }

    private function actorBlockReason(User $actor, Competition $competition): ?string
    {
        if (in_array($actor->role?->name, self::ADMIN_ROLES, true)) {
            return null;
        }

        $member = $this->governance->activeMemberFor($actor, $competition);
        if ($member === null || $member->canonical_seat !== CanonicalCommissionGovernance::ROLE_PREDSJEDNIK) {
            return self::UNAUTHORIZED_MESSAGE;
        }

        return $this->governance->memberActionBlockReason($member, $competition);
    }
}
