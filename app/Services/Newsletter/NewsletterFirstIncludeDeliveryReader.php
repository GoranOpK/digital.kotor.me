<?php

namespace App\Services\Newsletter;

use Illuminate\Support\Facades\Schema;

/**
 * NL-03 read-only adapter for successful first_include delivery evidence.
 * Does not write. Physical ledger table is owned by a later delivery package.
 */
final class NewsletterFirstIncludeDeliveryReader
{
    public function hasSuccessfulFirstInclude(int $userId, int $eventEntryId): bool
    {
        unset($userId, $eventEntryId);

        if (! Schema::hasTable('newsletter_delivery_ledger')) {
            return false;
        }

        return false;
    }
}
