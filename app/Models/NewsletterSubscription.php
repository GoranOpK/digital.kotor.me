<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kanonska Newsletter pretplata (TS-011 v1.0.2 / NL-01).
 * Pripada User nalogu. Nije e-mail SSOT.
 */
class NewsletterSubscription extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    public const SCOPE_ALL_EVENTS = 'all_events';

    public const SCOPE_SELECTED_ORGANIZERS = 'selected_organizers';

    protected $fillable = [
        'user_id',
        'status',
        'scope_mode',
        'include_without_organizer',
        'subscribed_at',
        'unsubscribed_at',
        'unsubscribe_token',
    ];

    protected function casts(): array
    {
        return [
            'include_without_organizer' => 'boolean',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(
            CulturalOrganizer::class,
            'newsletter_subscription_organizers',
            'newsletter_subscription_id',
            'cultural_organizer_id'
        )->withTimestamps();
    }

    public function sourceCoverages(): HasMany
    {
        return $this->hasMany(
            NewsletterSubscriptionSourceCoverage::class,
            'newsletter_subscription_id'
        );
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isUnsubscribed(): bool
    {
        return $this->status === self::STATUS_UNSUBSCRIBED;
    }

    public function usesAllEventsScope(): bool
    {
        return $this->scope_mode === self::SCOPE_ALL_EVENTS;
    }

    public function usesSelectedOrganizerScope(): bool
    {
        return $this->scope_mode === self::SCOPE_SELECTED_ORGANIZERS;
    }

    /**
     * Includes events without a canonical CulturalOrganizer relation.
     * In all_events this is implicit (not stored as SSOT on the flag).
     */
    public function includesEventsWithoutOrganizer(): bool
    {
        if ($this->usesAllEventsScope()) {
            return true;
        }

        return $this->usesSelectedOrganizerScope() && $this->include_without_organizer;
    }

    /**
     * NL-02 support: all_events is dynamic — no organizer snapshot/pivot.
     */
    public function applyAllEventsScope(): void
    {
        $this->scope_mode = self::SCOPE_ALL_EVENTS;
        $this->include_without_organizer = false;
        $this->save();
        $this->organizers()->detach();
    }

    /**
     * NL-02: selected_organizers stores exactly the submitted organizer set.
     *
     * @param  list<int>  $organizerIds
     */
    public function applySelectedOrganizerScope(array $organizerIds, bool $includeWithoutOrganizer): void
    {
        $this->scope_mode = self::SCOPE_SELECTED_ORGANIZERS;
        $this->include_without_organizer = $includeWithoutOrganizer;
        $this->save();
        $this->organizers()->sync(array_values(array_unique($organizerIds)));
    }

    /**
     * NL-02 support: unsubscribe keeps the row, clears active preferences.
     */
    public function applyUnsubscribeState(): void
    {
        $this->status = self::STATUS_UNSUBSCRIBED;
        $this->unsubscribed_at = now();
        $this->scope_mode = null;
        $this->include_without_organizer = false;
        $this->unsubscribe_token = null;
        $this->save();
        $this->organizers()->detach();
    }

    /**
     * NL-02 support: same row; previous preferences stay cleared.
     */
    public function applyReactivationState(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->subscribed_at = now();
        $this->unsubscribed_at = null;
        $this->scope_mode = null;
        $this->include_without_organizer = false;
        $this->save();
        $this->organizers()->detach();
    }
}
