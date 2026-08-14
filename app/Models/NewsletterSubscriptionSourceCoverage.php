<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NL-03 temporal source coverage. Not current-preference SSOT.
 */
class NewsletterSubscriptionSourceCoverage extends Model
{
    public const SOURCE_ORGANIZER = 'organizer';

    public const SOURCE_WITHOUT_ORGANIZER = 'without_organizer';

    public const SOURCE_ALL_EVENTS = 'all_events';

    protected $fillable = [
        'newsletter_subscription_id',
        'source_type',
        'cultural_organizer_id',
        'covered_since',
        'covered_until',
    ];

    protected function casts(): array
    {
        return [
            'covered_since' => 'datetime',
            'covered_until' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(NewsletterSubscription::class, 'newsletter_subscription_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(CulturalOrganizer::class, 'cultural_organizer_id');
    }

    public function isOpen(): bool
    {
        return $this->covered_until === null;
    }
}
