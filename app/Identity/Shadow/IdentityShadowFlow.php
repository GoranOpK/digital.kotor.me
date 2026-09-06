<?php

namespace App\Identity\Shadow;

final class IdentityShadowFlow
{
    public const EP_AVAILABILITY = 'ep_availability';

    public const KN_APPLICANT_TYPE = 'kn_applicant_type';

    public const KN_APPLICATION_PREFILL = 'kn_application_prefill';

    public const PROFILE_DISPLAY = 'profile_display';

    public const DASHBOARD_DISPLAY = 'dashboard_display';

    /**
     * @var list<string>
     */
    public const REQUIRED = [
        self::EP_AVAILABILITY,
        self::KN_APPLICANT_TYPE,
        self::KN_APPLICATION_PREFILL,
        self::PROFILE_DISPLAY,
        self::DASHBOARD_DISPLAY,
    ];
}
