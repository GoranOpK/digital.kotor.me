<?php

namespace App\Services\CulturalManifestationDomain;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ManifestationLifecycle
{
    /**
     * PO-MF-WF-02 — Moderator-kreirana MF: Nacrt / Vraćena → Na odobrenju.
     * PO-MF-WF-01 — Urednik-kreirana MF ne smije ući u approval tok.
     */
    public function submitForApproval(CulturalManifestation $manifestation, User $actor): CulturalManifestation
    {
        $this->assertNotEditorCreatedForApprovalFlow($manifestation);
        $this->assertTransition($manifestation, CulturalManifestation::STATUS_PENDING_APPROVAL);
        $this->assertReadyForSubmit($manifestation);

        return DB::transaction(function () use ($manifestation, $actor) {
            /** @var CulturalManifestation $locked */
            $locked = CulturalManifestation::query()
                ->whereKey($manifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertNotEditorCreatedForApprovalFlow($locked);
            $this->assertTransition($locked, CulturalManifestation::STATUS_PENDING_APPROVAL);
            $this->assertReadyForSubmit($locked);

            $locked->status = CulturalManifestation::STATUS_PENDING_APPROVAL;
            $locked->last_modified_by = $actor->id;
            if ($locked->first_submitted_at === null) {
                $locked->first_submitted_at = now();
            }
            $locked->save();

            return $locked->fresh();
        });
    }

    /**
     * PO-6B-06 — razlog vraćanja nije obavezan i ne persistira se.
     * PO-MF-WF-01 / PO-MF-WF-02 — samo Moderator-kreirana pending MF.
     */
    public function returnToRevision(
        CulturalManifestation $manifestation,
        User $actor,
    ): CulturalManifestation {
        $this->assertNotEditorCreatedForApprovalFlow($manifestation);
        $this->assertTransition($manifestation, CulturalManifestation::STATUS_RETURNED_FOR_REVISION);

        return DB::transaction(function () use ($manifestation, $actor) {
            /** @var CulturalManifestation $locked */
            $locked = CulturalManifestation::query()
                ->whereKey($manifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertNotEditorCreatedForApprovalFlow($locked);
            $this->assertTransition($locked, CulturalManifestation::STATUS_RETURNED_FOR_REVISION);

            $locked->status = CulturalManifestation::STATUS_RETURNED_FOR_REVISION;
            $locked->last_modified_by = $actor->id;
            $locked->save();

            return $locked->fresh();
        });
    }

    /**
     * PO-MF-WF-01 — Urednik-kreirana MF: Nacrt → Objavljena (isti publish gate BR-191).
     */
    public function publishDirectly(CulturalManifestation $manifestation, User $actor): CulturalManifestation
    {
        if (! $manifestation->isDraft()) {
            throw new CulturalEventDomainException(
                'Direktna objava dozvoljena je samo Manifestaciji u statusu Nacrt.'
            );
        }
        if (! $manifestation->isEditorCreated()) {
            throw new CulturalEventDomainException(
                'Direktna objava dozvoljena je samo Manifestaciji koju je kreirao Urednik.'
            );
        }
        $this->assertTransition($manifestation, CulturalManifestation::STATUS_PUBLISHED);
        $this->assertReadyForPublish($manifestation);

        return DB::transaction(function () use ($manifestation, $actor) {
            /** @var CulturalManifestation $locked */
            $locked = CulturalManifestation::query()
                ->whereKey($manifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isDraft()) {
                throw new CulturalEventDomainException(
                    'Direktna objava dozvoljena je samo Manifestaciji u statusu Nacrt.'
                );
            }
            if (! $locked->isEditorCreated()) {
                throw new CulturalEventDomainException(
                    'Direktna objava dozvoljena je samo Manifestaciji koju je kreirao Urednik.'
                );
            }
            $this->assertTransition($locked, CulturalManifestation::STATUS_PUBLISHED);
            $this->assertReadyForPublish($locked);

            $locked->status = CulturalManifestation::STATUS_PUBLISHED;
            $locked->published_at = now();
            $locked->last_modified_by = $actor->id;
            if ($locked->first_submitted_at === null) {
                $locked->first_submitted_at = now();
            }
            $locked->save();

            return $locked->fresh();
        });
    }

    /**
     * PO-MF-WF-02 — Moderator pending → Objavljena (Urednik odobrava).
     */
    public function publish(CulturalManifestation $manifestation, User $actor): CulturalManifestation
    {
        $this->assertTransition($manifestation, CulturalManifestation::STATUS_PUBLISHED);
        $this->assertReadyForPublish($manifestation);

        return DB::transaction(function () use ($manifestation, $actor) {
            /** @var CulturalManifestation $locked */
            $locked = CulturalManifestation::query()
                ->whereKey($manifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTransition($locked, CulturalManifestation::STATUS_PUBLISHED);
            $this->assertReadyForPublish($locked);

            $locked->status = CulturalManifestation::STATUS_PUBLISHED;
            $locked->published_at = now();
            $locked->last_modified_by = $actor->id;
            $locked->save();

            return $locked->fresh();
        });
    }

    private function assertNotEditorCreatedForApprovalFlow(CulturalManifestation $manifestation): void
    {
        if ($manifestation->isEditorCreated()) {
            throw new CulturalEventDomainException(
                'Manifestacija koju je kreirao Urednik ne prolazi postupak odobravanja; koristite direktnu objavu.'
            );
        }
    }

    public function cancel(CulturalManifestation $manifestation, User $actor): CulturalManifestation
    {
        $this->assertTransition($manifestation, CulturalManifestation::STATUS_CANCELLED);

        return DB::transaction(function () use ($manifestation, $actor) {
            /** @var CulturalManifestation $locked */
            $locked = CulturalManifestation::query()
                ->whereKey($manifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTransition($locked, CulturalManifestation::STATUS_CANCELLED);

            $locked->status = CulturalManifestation::STATUS_CANCELLED;
            $locked->cancelled_at = now();
            $locked->last_modified_by = $actor->id;
            $locked->save();

            return $locked->fresh();
        });
    }

    public function archiveIfEligible(CulturalManifestation $manifestation): CulturalManifestation
    {
        return DB::transaction(function () use ($manifestation) {
            /** @var CulturalManifestation $locked */
            $locked = CulturalManifestation::query()
                ->whereKey($manifestation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTransition($locked, CulturalManifestation::STATUS_ARCHIVED);

            $locked->status = CulturalManifestation::STATUS_ARCHIVED;
            $locked->archived_at = now();
            $locked->save();

            return $locked->fresh();
        });
    }

    public function assertReadyForSubmit(CulturalManifestation $manifestation): void
    {
        $linkedEvents = $manifestation->events()->count();
        if ($linkedEvents < 1) {
            throw new CulturalEventDomainException(
                'Za slanje Manifestacije na odobrenje potreban je najmanje jedan povezani Događaj.'
            );
        }
    }

    public function assertReadyForPublish(CulturalManifestation $manifestation): void
    {
        $linkedEvents = $manifestation->events()->count();
        if ($linkedEvents < 1) {
            throw new CulturalEventDomainException(
                'Manifestacija može biti objavljena samo ako ima najmanje jedan povezani Događaj.'
            );
        }

        $publishedEvents = $manifestation->events()
            ->where('status', CulturalEventEntry::STATUS_PUBLISHED)
            ->count();
        if ($publishedEvents < 1) {
            throw new CulturalEventDomainException(
                'Manifestacija može biti objavljena samo ako ima najmanje jedan Objavljeni Događaj.'
            );
        }
    }

    private function assertTransition(CulturalManifestation $manifestation, string $target): void
    {
        if (! $manifestation->canTransitionTo($target)) {
            throw new CulturalEventDomainException(sprintf(
                'Prelaz Manifestacije %s → %s nije dozvoljen.',
                $manifestation->statusLabel(),
                CulturalManifestation::STATUS_LABELS[$target] ?? $target
            ));
        }
    }
}

