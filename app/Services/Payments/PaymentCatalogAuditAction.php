<?php

namespace App\Services\Payments;

final class PaymentCatalogAuditAction
{
    public const TYPE_CREATED = 'payment_type.created';

    public const TYPE_UPDATED = 'payment_type.updated';

    public const TYPE_ACTIVATED = 'payment_type.activated';

    public const TYPE_DEACTIVATED = 'payment_type.deactivated';

    public const ACCOUNT_CREATED = 'payment_account.created';

    public const ACCOUNT_UPDATED = 'payment_account.updated';

    public const ACCOUNT_ACTIVATED = 'payment_account.activated';

    public const ACCOUNT_DEACTIVATED = 'payment_account.deactivated';

    public const TYPE_AVAILABILITY_ADDED = 'payment_type_availability.added';

    public const TYPE_AVAILABILITY_ACTIVATED = 'payment_type_availability.activated';

    public const TYPE_AVAILABILITY_DEACTIVATED = 'payment_type_availability.deactivated';

    public const ACCOUNT_AVAILABILITY_ADDED = 'payment_account_availability.added';

    public const ACCOUNT_AVAILABILITY_ACTIVATED = 'payment_account_availability.activated';

    public const ACCOUNT_AVAILABILITY_DEACTIVATED = 'payment_account_availability.deactivated';

    public const MODULE_ENABLED = 'ep_setting.enabled';

    public const MODULE_DISABLED = 'ep_setting.disabled';
}
